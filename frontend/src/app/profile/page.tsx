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
    <div className="mx-auto max-w-lg px-4 py-8">
      <div className="mb-6 flex items-center justify-between">
        <h1 className="text-2xl font-bold">My Profile</h1>
        <div className="flex gap-2">
          <button
            onClick={() => router.push('/sessions')}
            className="rounded border border-gray-300 px-3 py-1.5 text-sm hover:bg-gray-50"
          >
            ← Sessions
          </button>
          <button
            onClick={handleLogout}
            className="rounded bg-gray-800 px-3 py-1.5 text-sm text-white hover:bg-gray-700"
          >
            Sign out
          </button>
        </div>
      </div>

      <div className="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
        <form onSubmit={handleSubmit} className="space-y-4">
          {globalError && (
            <p className="rounded bg-red-100 px-3 py-2 text-sm text-red-700">{globalError}</p>
          )}
          {success && (
            <p className="rounded bg-green-100 px-3 py-2 text-sm text-green-700">{success}</p>
          )}

          <div>
            <label className="block text-sm font-medium mb-1" htmlFor="name">Name</label>
            <input
              id="name" type="text" value={name} onChange={e => setName(e.target.value)}
              className={`w-full rounded border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 ${fieldErrors.name ? 'border-red-500' : 'border-gray-300'}`}
            />
            {fieldErrors.name && <p className="mt-1 text-xs text-red-600">{fieldErrors.name}</p>}
          </div>

          <div>
            <label className="block text-sm font-medium mb-1" htmlFor="email">Email</label>
            <input
              id="email" type="email" value={email} onChange={e => setEmail(e.target.value)}
              className={`w-full rounded border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 ${fieldErrors.email ? 'border-red-500' : 'border-gray-300'}`}
            />
            {fieldErrors.email && <p className="mt-1 text-xs text-red-600">{fieldErrors.email}</p>}
          </div>

          <button
            type="submit" disabled={loading}
            className="w-full rounded bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700 disabled:opacity-50"
          >
            {loading ? 'Saving…' : 'Save changes'}
          </button>
        </form>

        <div className="mt-4 border-t border-gray-100 pt-4">
          <p className="text-xs text-gray-500">
            Roles: {user?.roles.join(', ')}
          </p>
          <p className="text-xs text-gray-400 mt-1">
            Member since {user ? new Date(user.createdAt).toLocaleDateString() : '—'}
          </p>
        </div>
      </div>
    </div>
  );
}
