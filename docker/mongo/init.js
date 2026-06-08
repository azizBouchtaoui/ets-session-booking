const dbName = process.env.MONGO_INITDB_DATABASE || 'ets_reservations';
const appUser = process.env.MONGO_APP_USERNAME || 'app_user';
const appPassword = process.env.MONGO_APP_PASSWORD || 'app_password';

db = db.getSiblingDB(dbName);

db.createUser({
    user: appUser,
    pwd: appPassword,
    roles: [{ role: 'readWrite', db: dbName }],
});

db.createCollection('users');
db.createCollection('sessions');
db.createCollection('reservations');

db.users.createIndex({ email: 1 }, { unique: true });
db.sessions.createIndex({ scheduledAt: 1 });
db.reservations.createIndex({ sessionId: 1, userId: 1 }, { unique: true });
