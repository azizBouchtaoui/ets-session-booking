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
    <div className="mx-auto max-w-4xl px-4 py-8">
      {/* Header */}
      <div className="mb-8 flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-bold">Sessions</h1>
          <p className="text-sm text-gray-600 mt-1">Welcome, {user?.name}</p>
        </div>
        <div className="flex gap-3">
          <button
            onClick={() => router.push('/profile')}
            className="rounded border border-gray-300 px-3 py-1.5 text-sm hover:bg-gray-50"
          >
            Profile
          </button>
          <button
            onClick={handleLogout}
            className="rounded bg-gray-800 px-3 py-1.5 text-sm text-white hover:bg-gray-700"
          >
            Sign out
          </button>
        </div>
      </div>

      {actionError && (
        <p className="mb-4 rounded bg-red-100 px-3 py-2 text-sm text-red-700">{actionError}</p>
      )}

      {/* Session list */}
      {loading ? (
        <p className="text-gray-500">Loading sessions…</p>
      ) : data?.items.length === 0 ? (
        <p className="text-gray-500">No sessions available.</p>
      ) : (
        <div className="space-y-4">
          {data?.items.map((session: Session) => {
            const reserved = reservedSessionIds.has(session.id);
            const full = session.availableSpots === 0;

            return (
              <div key={session.id} className="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                <div className="flex items-start justify-between gap-4">
                  <div className="space-y-1">
                    <h2 className="font-semibold text-lg">{session.language}</h2>
                    <p className="text-sm text-gray-600">
                      📅 {new Date(session.scheduledAt).toLocaleString()}
                    </p>
                    <p className="text-sm text-gray-600">📍 {session.location}</p>
                    <p className="text-sm text-gray-600">
                      🎯 {session.availableSpots} / {session.capacity} spots available
                    </p>
                  </div>
                  <div className="flex flex-col gap-2 shrink-0">
                    {reserved ? (
                      <button
                        onClick={() => handleCancel(session.id)}
                        className="rounded border border-red-300 px-3 py-1.5 text-sm text-red-600 hover:bg-red-50"
                      >
                        Cancel
                      </button>
                    ) : (
                      <button
                        onClick={() => handleReserve(session.id)}
                        disabled={full}
                        className="rounded bg-blue-600 px-3 py-1.5 text-sm text-white hover:bg-blue-700 disabled:opacity-40"
                      >
                        {full ? 'Full' : 'Reserve'}
                      </button>
                    )}
                    {isAdmin && (
                      <button
                        onClick={() => handleDeleteSession(session.id)}
                        className="rounded border border-gray-200 px-3 py-1.5 text-xs text-gray-500 hover:bg-gray-50"
                      >
                        Delete
                      </button>
                    )}
                  </div>
                </div>
              </div>
            );
          })}
        </div>
      )}

      {/* Pagination */}
      {data && data.pages > 1 && (
        <div className="mt-6 flex items-center justify-center gap-2">
          <button
            onClick={() => setPage(p => Math.max(1, p - 1))}
            disabled={page === 1}
            className="rounded border px-3 py-1 text-sm disabled:opacity-40"
          >
            Previous
          </button>
          <span className="text-sm text-gray-600">
            Page {page} of {data.pages}
          </span>
          <button
            onClick={() => setPage(p => Math.min(data.pages, p + 1))}
            disabled={page === data.pages}
            className="rounded border px-3 py-1 text-sm disabled:opacity-40"
          >
            Next
          </button>
        </div>
      )}
    </div>
  );
}
