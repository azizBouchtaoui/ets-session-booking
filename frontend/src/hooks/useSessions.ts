'use client';

import { useCallback, useEffect, useState } from 'react';
import { api } from '@/lib/api';
import type { PaginatedSessions } from '@/lib/types';

export function useSessions(page = 1, limit = 10) {
  const [data, setData] = useState<PaginatedSessions | null>(null);
  const [loading, setLoading] = useState(true);

  const load = useCallback(async () => {
    setLoading(true);
    try {
      const result = await api.get<PaginatedSessions>(
        `/api/sessions?page=${page}&limit=${limit}`
      );
      setData(result);
    } finally {
      setLoading(false);
    }
  }, [page, limit]);

  useEffect(() => { load(); }, [load]);

  return { data, loading, reload: load };
}
