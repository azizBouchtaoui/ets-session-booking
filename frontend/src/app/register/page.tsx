'use client';

import { useState, FormEvent } from 'react';
import Link from 'next/link';
import { useRouter } from 'next/navigation';
import { api } from '@/lib/api';
import type { ApiError } from '@/lib/api';

export default function RegisterPage() {
  const router = useRouter();

  const [name, setName] = useState('');
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [fieldErrors, setFieldErrors] = useState<Record<string, string>>({});
  const [globalError, setGlobalError] = useState('');
  const [loading, setLoading] = useState(false);

  const handleSubmit = async (e: FormEvent) => {
    e.preventDefault();
    setFieldErrors({});
    setGlobalError('');
    setLoading(true);
    try {
      await api.post('/api/auth/register', { email, name, password });
      router.push('/login?registered=1');
    } catch (err) {
      const apiErr = err as ApiError;
      if (apiErr.errors) {
        setFieldErrors(apiErr.errors);
      } else {
        setGlobalError(apiErr.message ?? 'Registration failed.');
      }
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="flex min-h-screen items-center justify-center px-4">
      <div className="w-full max-w-sm space-y-6">
        <h1 className="text-2xl font-bold text-center">Create account</h1>

        <form onSubmit={handleSubmit} className="space-y-4">
          {globalError && (
            <p className="rounded bg-red-100 px-3 py-2 text-sm text-red-700">{globalError}</p>
          )}

          <Field id="name" label="Full name" type="text" value={name}
            onChange={setName} error={fieldErrors.name} autoComplete="name" />

          <Field id="email" label="Email" type="email" value={email}
            onChange={setEmail} error={fieldErrors.email} autoComplete="email" />

          <Field id="password" label="Password (min. 8 chars)" type="password" value={password}
            onChange={setPassword} error={fieldErrors.password} autoComplete="new-password" />

          <button
            type="submit" disabled={loading}
            className="w-full rounded bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700 disabled:opacity-50"
          >
            {loading ? 'Creating account…' : 'Register'}
          </button>
        </form>

        <p className="text-center text-sm text-gray-600">
          Already have an account?{' '}
          <Link href="/login" className="text-blue-600 hover:underline">Sign in</Link>
        </p>
      </div>
    </div>
  );
}

function Field({
  id, label, type, value, onChange, error, autoComplete,
}: {
  id: string; label: string; type: string; value: string;
  onChange: (v: string) => void; error?: string; autoComplete?: string;
}) {
  return (
    <div>
      <label className="block text-sm font-medium mb-1" htmlFor={id}>{label}</label>
      <input
        id={id} type={type} required autoComplete={autoComplete}
        value={value} onChange={e => onChange(e.target.value)}
        className={`w-full rounded border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 ${error ? 'border-red-500' : 'border-gray-300'}`}
      />
      {error && <p className="mt-1 text-xs text-red-600">{error}</p>}
    </div>
  );
}
