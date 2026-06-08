const API_BASE = process.env.NEXT_PUBLIC_API_URL ?? 'http://localhost:8080';

export type ApiError = { message?: string; errors?: Record<string, string> };

async function apiFetch<T>(
  path: string,
  options: RequestInit = {}
): Promise<T> {
  const res = await fetch(`${API_BASE}${path}`, {
    ...options,
    credentials: 'include',
    headers: {
      'Content-Type': 'application/json',
      ...(options.headers ?? {}),
    },
  });

  if (res.status === 204) return undefined as unknown as T;

  const data = await res.json().catch(() => ({}));

  if (!res.ok) {
    const err: ApiError = data;
    throw err;
  }

  return data as T;
}

export const api = {
  post: <T>(path: string, body: unknown) =>
    apiFetch<T>(path, { method: 'POST', body: JSON.stringify(body) }),

  get: <T>(path: string) => apiFetch<T>(path, { method: 'GET' }),

  put: <T>(path: string, body: unknown) =>
    apiFetch<T>(path, { method: 'PUT', body: JSON.stringify(body) }),

  del: (path: string) => apiFetch<void>(path, { method: 'DELETE' }),
};
