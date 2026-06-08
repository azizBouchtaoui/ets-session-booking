'use client';

import { useCallback, useEffect, useState } from 'react';
import { api } from '@/lib/api';
import type { Reservation } from '@/lib/types';

export function useReservations() {
  const [reservations, setReservations] = useState<Reservation[]>([]);

  const load = useCallback(async () => {
    try {
      const result = await api.get<Reservation[]>('/api/reservations');
      setReservations(result);
    } catch {
      setReservations([]);
    }
  }, []);

  useEffect(() => { load(); }, [load]);

  return { reservations, reload: load };
}
