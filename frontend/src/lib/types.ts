export interface User {
  id: string;
  email: string;
  name: string;
  roles: string[];
  createdAt: string;
}

export interface Session {
  id: string;
  language: string;
  scheduledAt: string;
  location: string;
  capacity: number;
  availableSpots: number;
  createdAt: string;
}

export interface Reservation {
  id: string;
  userId: string;
  sessionId: string;
  reservedAt: string;
}

export interface PaginatedSessions {
  items: Session[];
  total: number;
  page: number;
  limit: number;
  pages: number;
}
