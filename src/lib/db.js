/**
 * Manage all message chats to and from the server for effective management
 */
export class ChatSessionDB {
    constructor(dbName = 'chatSessionDB', storeName = 'chatSessions') {
      this.dbName = dbName;
      this.storeName = storeName;
      this.db = null;
    }
  
    async openDatabase() {
      return new Promise((resolve, reject) => {
        if (this.db) {
          resolve(this.db);
          return;
        }
  
        const request = indexedDB.open(this.dbName, 1);
  
        request.onerror = (event) => {
          reject(`Database error: ${event.target.errorCode}`);
        };
  
        request.onsuccess = (event) => {
          this.db = event.target.result;
          resolve(this.db);
        };
  
        request.onupgradeneeded = (event) => {
          const db = event.target.result;
          if (!db.objectStoreNames.contains(this.storeName)) {
            db.createObjectStore(this.storeName, { keyPath: 'sessionId' });
          }
        };
      });
    }
  
    async storeSession(sessionData) {
      const db = await this.openDatabase();
      return new Promise((resolve, reject) => {
        const transaction = db.transaction([this.storeName], 'readwrite');
        const store = transaction.objectStore(this.storeName);
        const request = store.put(sessionData); // Use put to store or update
  
        request.onsuccess = () => {
          resolve(); // No need to return an ID
        };
  
        request.onerror = (event) => {
          reject(`Error storing session: ${event.target.errorCode}`);
        };
      });
    }
  
    async getSession(sessionId) {
      const db = await this.openDatabase();
      return new Promise((resolve, reject) => {
        const transaction = db.transaction([this.storeName], 'readonly');
        const store = transaction.objectStore(this.storeName);
        const request = store.get(sessionId);
  
        request.onsuccess = (event) => {
          resolve(event.target.result);
        };
  
        request.onerror = (event) => {
          reject(`Error getting session: ${event.target.errorCode}`);
        };
      });
    }
  
    async deleteSession(sessionId) {
      const db = await this.openDatabase();
      return new Promise((resolve, reject) => {
        const transaction = db.transaction([this.storeName], 'readwrite');
        const store = transaction.objectStore(this.storeName);
        const request = store.delete(sessionId);
  
        request.onsuccess = () => {
          resolve();
        };
  
        request.onerror = (event) => {
          reject(`Error deleting session: ${event.target.errorCode}`);
        };
      });
    }
}