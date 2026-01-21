/**
 * 
 * @param payload A JSON payload
 * @param url The Address to connect to
 * @param nonce Wordpress Security Key
 * @param method Method that the API will accept
 */
export const sendRequestToApi = (endpoint, url, nonce, method, payload = {}) => {
    //console.log(endpoint, url, nonce, method, payload);
    let destination = url;
    let data;
    
    if(method === 'GET'){
      const keys = Object.keys(payload);
      if( keys.length > 0 ){
        destination += `?${keys[0]}=${payload[keys[0]]}`;
        keys.forEach((item,index) => {
          if(index !== 0){
            destination += `&${item}=${payload[item]}`
          }
        });
      }
    } else if (method === 'POST') {
      data = JSON.stringify(payload);
    }

    return new Promise((resolve, reject) => {
      const formData = new FormData();
      
      formData.append('action', 'emmerce_chat_message');
      
      if(method === 'POST') {
        formData.append('data', data);
      }

      formData.append('url', destination);
      formData.append('method', method);
      formData.append('security', nonce);

      fetch(endpoint, {
        method: 'POST',
        body: formData,
      })
        .then(response => {
          if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
          }
          return response.json();
        })
        .then(data => {
          if (data.success) {
            resolve(data.data);
          } else {
            reject(data.data);
          }
        })
        .catch(error => {
          reject(error);
        });
    });
}

/**
 * Return customized greetings to the user based on the time of the day
 */
export const getGreeting = () => {
  const now = new Date();
  const hour = now.getHours();

  if (hour >= 3 && hour < 12) {
    return "Good morning";
  } else if (hour >= 12 && hour < 17) {
    return "Good afternoon";
  } else if (hour >= 17 && hour < 22) {
    return "Good evening";
  } else {
    return "Good night";
  }
}

/**
 * Web socket manager class for managing web socket connections to and from
 * the chat backend
 */
export class WebSocketManager {
  constructor(url, options = {}) {
    this.url = url;
    this.reconnectInterval = options.reconnectInterval || 5000;
    this.socket = null;
    this.connected = false;
    this.messageListeners = [];
    this.errorListeners = [];
    this.connectListeners = [];
    this.disconnectListeners = [];
    this.reconnectTimeout = null;
    this.messageTypeListeners = {};
    this.pendingMessages = []; // Queue for messages sent while disconnected
    this.isReconnecting = false; // Flag to prevent multiple reconnection attempts
    this.shouldReconnect = true; // Flag to control auto-reconnection behavior

    this.connect();
  }

  connect() {
    // Don't connect if we've been explicitly closed
    if (!this.shouldReconnect) return;

    if (this.socket) {
      this.socket.close();
    }

    this.socket = new WebSocket(this.url);

    this.socket.onopen = () => {
      this.connected = true;
      this.isReconnecting = false;
      this.errorListeners.forEach((listener) => listener(null));
      this.connectListeners.forEach((listener) => listener());
      if (this.reconnectTimeout) clearTimeout(this.reconnectTimeout);
      
      // Send any queued messages
      this.flushPendingMessages();
    };

    this.socket.onmessage = (event) => {
      try {
        const data = JSON.parse(event.data);
        this.messageListeners.forEach((listener) => listener(data));
        if (data && data.type && this.messageTypeListeners[data.type]) {
          this.messageTypeListeners[data.type].forEach((listener) => listener(data));
        }
      } catch (e) {
        this.messageListeners.forEach((listener) => listener(event.data));
      }
    };

    this.socket.onerror = (event) => {
      this.errorListeners.forEach((listener) => listener(event));
      this.connected = false;
    };

    this.socket.onclose = () => {
      this.connected = false;
      this.disconnectListeners.forEach((listener) => listener());
      
      // Only reconnect if we should (not manually closed)
      if (this.shouldReconnect) {
        this.reconnect();
      }
    };
  }

  reconnect() {
    // Only reconnect if we should
    if (!this.shouldReconnect) return;
    
    if (this.reconnectTimeout) clearTimeout(this.reconnectTimeout);
    this.reconnectTimeout = setTimeout(this.connect.bind(this), this.reconnectInterval);
  }

  /**
   * Flushes all pending messages once connection is re-established
   */
  flushPendingMessages() {
    while (this.pendingMessages.length > 0) {
      const message = this.pendingMessages.shift();
      if (this.socket && this.connected) {
        this.socket.send(JSON.stringify(message));
      } else {
        // If connection was lost again, put message back
        this.pendingMessages.unshift(message);
        break;
      }
    }
  }

  /**
   * Sends a message, automatically reconnecting if needed
   * @param {*} data - The data to send
   * @returns {Promise<void>} - Resolves when message is sent or queued
   */
  async send(data) {
    // If connected, send immediately
    if (this.socket && this.connected) {
      this.socket.send(JSON.stringify(data));
      return;
    }

    // Queue the message
    this.pendingMessages.push(data);

    // If not already reconnecting, initiate reconnection
    if (!this.isReconnecting && this.shouldReconnect) {
      this.isReconnecting = true;
      
      // Clear any existing reconnect timeout
      if (this.reconnectTimeout) {
        clearTimeout(this.reconnectTimeout);
        this.reconnectTimeout = null;
      }
      
      // Attempt immediate reconnection
      this.connect();
    }
  }

  addMessageListener(listener) {
    this.messageListeners.push(listener);
  }

  addErrorListener(listener) {
    this.errorListeners.push(listener);
  }

  addConnectListener(listener) {
    this.connectListeners.push(listener);
  }
  
  addDisconnectListener(listener) {
    this.disconnectListeners.push(listener);
  }

  addMessageTypeListener(messageType, listener) {
    if (!this.messageTypeListeners[messageType]) {
      this.messageTypeListeners[messageType] = [];
    }
    this.messageTypeListeners[messageType].push(listener);
  }

  /**
   * Gets the count of pending messages waiting to be sent
   */
  getPendingMessageCount() {
    return this.pendingMessages.length;
  }

  /**
   * Clears all pending messages (useful for cleanup or reset scenarios)
   */
  clearPendingMessages() {
    this.pendingMessages = [];
  }

  /**
   * Closes the WebSocket connection and prevents auto-reconnection
   */
  close() {
    // Prevent any further reconnection attempts
    this.shouldReconnect = false;
    
    // Close the socket
    if (this.socket) {
      this.socket.close();
      this.socket = null;
    }
    
    // Clear any pending reconnection timeout
    if (this.reconnectTimeout) {
      clearTimeout(this.reconnectTimeout);
      this.reconnectTimeout = null;
    }
    
    // Clear pending messages
    this.clearPendingMessages();
    
    // Clear all listeners to prevent memory leaks
    this.messageListeners = [];
    this.errorListeners = [];
    this.connectListeners = [];
    this.disconnectListeners = [];
    this.messageTypeListeners = {};
    
    // Reset connection state
    this.connected = false;
    this.isReconnecting = false;
  }

  /**
   * Completely destroys the WebSocket instance
   * Use this when you're done with the WebSocket and won't need it again
   */
  destroy() {
    this.close();
  }
}

/**
 * Generate color gradients based on a hex value
 */
export const generateColorGradients = (hexColor, steps = 5) => {
  const hexRegex = /^#([0-9A-Fa-f]{3}){1,2}$/;
  if (!hexRegex.test(hexColor)) {
    return "Invalid hex color";
  }

  const r = parseInt(hexColor.slice(1, 3), 16);
  const g = parseInt(hexColor.slice(3, 5), 16);
  const b = parseInt(hexColor.slice(5, 7), 16);

  const gradients = [];

  for (let i = 0; i <= steps; i++) {
    const factor = i / steps;

    const lighterR = Math.round(r + (255 - r) * factor);
    const lighterG = Math.round(g + (255 - g) * factor);
    const lighterB = Math.round(b + (255 - b) * factor);

    const darkerR = Math.round(r * (1 - factor));
    const darkerG = Math.round(g * (1 - factor));
    const darkerB = Math.round(b * (1 - factor));

    const lighterHex = `#${lighterR.toString(16).padStart(2, '0')}${lighterG.toString(16).padStart(2, '0')}${lighterB.toString(16).padStart(2, '0')}`;
    const darkerHex = `#${darkerR.toString(16).padStart(2, '0')}${darkerG.toString(16).padStart(2, '0')}${darkerB.toString(16).padStart(2, '0')}`;

    gradients.push({ lighter: lighterHex, darker: darkerHex });
  }

  return gradients;
}

/**
 * Validate phone numbers
 */
export const validatePhoneNumber = (phoneNumber) => {
  const cleanedNumber = phoneNumber.replace(/[^+\d]/g, '');
  const kenyanRegex = /^(?:\+?254|0)?(7(?:(?:[0-9]){8}))$/;
  const usCanadaRegex = /^(1|)?[-.\s]?\(?(\d{3})\)?[-.\s]?(\d{3})[-.\s]?(\d{4})$/;
  const ukRegex = /^(?:0|\+44) ?(?:\\(0\\))? ?(?:[0-9] ?){8,9}[0-9]$/;
  const internationalRegex = /^\+(?:[0-9] ?){6,14}[0-9]$/;

  if (internationalRegex.test(cleanedNumber)) {
    return true;
  } else if (usCanadaRegex.test(cleanedNumber)) {
    return true;
  } else if (ukRegex.test(cleanedNumber)){
    return true;
  } else if (kenyanRegex.test(cleanedNumber)){
    return true;
  } else {
    return false;
  }
}
