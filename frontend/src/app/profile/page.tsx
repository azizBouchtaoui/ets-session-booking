'use client';

import { useState, FormEvent, useEffect } from 'react';
import { useRouter } from 'next/navigation';
import { useAuth } from '@/contexts/AuthContext';
import { api } from '@/lib/api';
import type { ApiError } from '@/lib/api';

export default function ProfilePage() {
  const { user, refresh, logout } = useAuth();
  const router = useRouter();

  const [name, setName] = useState('');
  const [email, setEmail] = useState('');
  const [fieldErrors, setFieldErrors] = useState<Record<string, string>>({});
  const [globalError, setGlobalError] = useState('');
  const [success, setSuccess] = useState('');
  const [loading, setLoading] = useState(false);

  useEffect(() => {
    if (user) {
      setName(user.name);
      setEmail(user.email);
    }
  }, [user]);

  const handleSubmit = async (e: FormEvent) => {
    e.preventDefault();
    setFieldErrors({});
    setGlobalError('');
    setSuccess('');
    setLoading(true);
    try {
      await api.put('/api/users/me', { name, email });
      await refresh();
      setSuccess('Profile updated successfully.');
    } catch (err) {
      const apiErr = err as ApiError;
      if (apiErr.errors) setFieldErrors(apiErr.errors);
      else setGlobalError(apiErr.message ?? 'Update failed.');
    } finally {
      setLoading(false);
    }
  };

  const handleLogout = async () => {
    await logout();
    router.push('/login');
  };

  return (
    <div className="min-h-screen bg-gray-50">
      {/* Top navigation */}
      <header className="bg-white border-b border-gray-200">
        <div className="mx-auto max-w-5xl px-4 sm:px-6 h-16 flex items-center justify-between">
          <div className="flex items-center gap-3">
            <div className="w-8 h-8 rounded-lg bg-blue-600 flex items-center justify-center">
              <svg className="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z" />
              </svg>
            </div>
            <span className="font-semibold text-gray-900">ETS Reservations</span>
          </div>
          <div className="flex items-center gap-3">
            <button
              onClick={() => router.push('/sessions')}
              className="rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-50 transition"
            >
              ← Sessions
            </button>
            <button
              onClick={handleLogout}
              className="rounded-lg bg-gray-900 px-3 py-1.5 text-sm font-medium text-white hover:bg-gray-700 transition"
            >
              Sign out
            </button>
          </div>
        </div>
      </header>

      <main className="mx-auto max-w-lg px-4 sm:px-6 py-8">
        <div className="mb-6">
          <h1 className="text-xl font-semibold text-gray-900">My Profile</h1>
          <p className="text-sm text-gray-500 mt-0.5">Update your personal information</p>
        </div>

        <div className="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
          <form onSubmit={handleSubmit} className="space-y-5">
            {globalError && (
              <div className="rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700">
                {globalError}
              </div>
            )}
            {success && (
              <div className="rounded-lg bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-700">
                {success}
              </div>
            )}

            <div className="space-y-1.5">
              <label className="block text-sm font-medium text-gray-700" htmlFor="name">Full name</label>
              <input
                id="name" type="text" value={name} onChange={e => setName(e.target.value)}
                className={`w-full rounded-lg border bg-white px-3.5 py-2.5 text-sm text-gray-900 shadow-sm focus:outline-none focus:ring-2 transition ${
                  fieldErrors.name
                    ? 'border-red-400 focus:border-red-500 focus:ring-red-500/20'
                    : 'border-gray-300 focus:border-blue-500 focus:ring-blue-500/20'
                }`}
              />
              {fieldErrors.name && <p className="text-xs text-red-600">{fieldErrors.name}</p>}
            </div>

            <div className="space-y-1.5">
              <label className="block text-sm font-medium text-gray-700" htmlFor="email">Email address</label>
              <input
                id="email" type="email" value={email} onChange={e => setEmail(e.target.value)}
                className={`w-full rounded-lg border bg-white px-3.5 py-2.5 text-sm text-gray-900 shadow-sm focus:outline-none focus:ring-2 transition ${
                  fieldErrors.email
                    ? 'border-red-400 focus:border-red-500 focus:ring-red-500/20'
                    : 'border-gray-300 focus:border-blue-500 focus:ring-blue-500/20'
                }`}
              />
              {fieldErrors.email && <p className="text-xs text-red-600">{fieldErrors.email}</p>}
            </div>

            <button
              type="submit" disabled={loading}
              className="w-full rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed transition"
            >
              {loading ? 'Saving…' : 'Save changes'}
            </button>
          </form>

          <div className="mt-5 pt-5 border-t border-gray-100 flex items-center justify-between">
            <div>
              <p className="text-xs font-medium text-gray-500">Role</p>
              <p className="text-sm text-gray-700 mt-0.5">{user?.roles.join(', ')}</p>
            </div>
            <div className="text-right">
              <p className="text-xs font-medium text-gray-500">Member since</p>
              <p className="text-sm text-gray-700 mt-0.5">
                {user ? new Date(user.createdAt).toLocaleDateString('en-GB', {dateStyle: 'medium'}) : '—'}
              </p>
            </div>
          </div>
        </div>
      </main>
    </div>
  );
}
