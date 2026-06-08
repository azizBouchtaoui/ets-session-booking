'use client';

import { useState } from 'react';
import { useRouter } from 'next/navigation';
import { useAuth } from '@/contexts/AuthContext';
import { api } from '@/lib/api';
import { useSessions } from '@/hooks/useSessions';
import { useReservations } from '@/hooks/useReservations';
import type { Session } from '@/lib/types';
import type { ApiError } from '@/lib/api';

export default function SessionsPage() {
  const { user, logout } = useAuth();
  const router = useRouter();

  const [page, setPage] = useState(1);
  const { data, loading, reload } = useSessions(page);
  const { reservations, reload: reloadReservations } = useReservations();

  const [actionError, setActionError] = useState('');

  const isAdmin = user?.roles.includes('ROLE_ADMIN');

  const reservedSessionIds = new Set(reservations.map(r => r.sessionId));

  const handleReserve = async (sessionId: string) => {
    setActionError('');
    try {
      await api.post('/api/reservations', { sessionId });
      await Promise.all([reload(), reloadReservations()]);
    } catch (err) {
      setActionError((err as ApiError).message ?? 'Reservation failed.');
    }
  };

  const handleCancel = async (sessionId: string) => {
    setActionError('');
    const reservation = reservations.find(r => r.sessionId === sessionId);
    if (!reservation) return;
    try {
      await api.del(`/api/reservations/${reservation.id}`);
      await Promise.all([reload(), reloadReservations()]);
    } catch (err) {
      setActionError((err as ApiError).message ?? 'Cancellation failed.');
    }
  };

  const handleDeleteSession = async (sessionId: string) => {
    setActionError('');
    try {
      await api.del(`/api/sessions/${sessionId}`);
      await reload();
    } catch (err) {
      setActionError((err as ApiError).message ?? 'Deletion failed.');
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
            <span className="hidden sm:block text-sm text-gray-500">{user?.name}</span>
            <button
              onClick={() => router.push('/profile')}
              className="rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-50 transition"
            >
              Profile
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

      {/* Main content */}
      <main className="mx-auto max-w-5xl px-4 sm:px-6 py-8">
        <div className="mb-6">
          <h1 className="text-xl font-semibold text-gray-900">Available Sessions</h1>
          <p className="text-sm text-gray-500 mt-0.5">Browse and reserve your language test session</p>
        </div>

        {actionError && (
          <div className="mb-5 rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700">
            {actionError}
          </div>
        )}

        {loading ? (
          <div className="flex items-center justify-center py-20 text-gray-400 text-sm">Loading sessions…</div>
        ) : data?.items.length === 0 ? (
          <div className="flex flex-col items-center justify-center py-20 text-center">
            <div className="w-12 h-12 rounded-full bg-gray-100 flex items-center justify-center mb-3">
              <svg className="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
              </svg>
            </div>
            <p className="text-gray-600 font-medium">No sessions available</p>
            <p className="text-gray-400 text-sm mt-1">Check back later for upcoming sessions</p>
          </div>
        ) : (
          <div className="grid gap-4 sm:grid-cols-2">
            {data?.items.map((session: Session) => {
              const reserved = reservedSessionIds.has(session.id);
              const full = session.availableSpots === 0;

              return (
                <div key={session.id} className="bg-white rounded-xl border border-gray-200 shadow-sm p-5 flex flex-col gap-4 hover:shadow-md transition">
                  <div className="flex items-start justify-between gap-2">
                    <div>
                      <span className="inline-block rounded-full bg-blue-50 text-blue-700 text-xs font-semibold px-2.5 py-0.5 mb-2">
                        {session.language}
                      </span>
                      <p className="text-sm text-gray-700 flex items-center gap-1.5">
                        <svg className="w-4 h-4 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        {new Date(session.scheduledAt).toLocaleString('en-GB', {dateStyle:'medium', timeStyle:'short'})}
                      </p>
                      <p className="text-sm text-gray-700 flex items-center gap-1.5 mt-1">
                        <svg className="w-4 h-4 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        {session.location}
                      </p>
                    </div>
                    <div className="text-right shrink-0">
                      <p className={`text-lg font-bold ${full ? 'text-red-500' : 'text-green-600'}`}>{session.availableSpots}</p>
                      <p className="text-xs text-gray-400">of {session.capacity} spots</p>
                    </div>
                  </div>

                  <div className="flex items-center gap-2 pt-1 border-t border-gray-100">
                    {reserved ? (
                      <button
                        onClick={() => handleCancel(session.id)}
                        className="flex-1 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm font-medium text-red-600 hover:bg-red-100 transition"
                      >
                        Cancel reservation
                      </button>
                    ) : (
                      <button
                        onClick={() => handleReserve(session.id)}
                        disabled={full}
                        className="flex-1 rounded-lg bg-blue-600 px-3 py-2 text-sm font-medium text-white hover:bg-blue-700 disabled:opacity-40 disabled:cursor-not-allowed transition"
                      >
                        {full ? 'Fully booked' : 'Reserve a spot'}
                      </button>
                    )}
                    {isAdmin && (
                      <button
                        onClick={() => handleDeleteSession(session.id)}
                        className="rounded-lg border border-gray-200 px-3 py-2 text-xs text-gray-400 hover:text-red-500 hover:border-red-200 transition"
                        title="Delete session"
                      >
                        <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                      </button>
                    )}
                  </div>
                </div>
              );
            })}
          </div>
        )}

        {/* Pagination */}
        {data && data.pages > 1 && (
          <div className="mt-8 flex items-center justify-center gap-2">
            <button
              onClick={() => setPage(p => Math.max(1, p - 1))}
              disabled={page === 1}
              className="rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50 disabled:opacity-40 transition"
            >
              ← Previous
            </button>
            <span className="text-sm text-gray-500 px-2">
              Page {page} of {data.pages}
            </span>
            <button
              onClick={() => setPage(p => Math.min(data.pages, p + 1))}
              disabled={page === data.pages}
              className="rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50 disabled:opacity-40 transition"
            >
              Next →
            </button>
          </div>
        )}
      </main>
    </div>
  );
}
