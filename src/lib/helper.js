/**
 * 
 * @param payload A JSON payload
 * @param url The Address to connect to
 * @param nonce Wordpress Security Key
 * @param method Method that the API will accept
 */
export const sendRequestToApi = (endpoint, url, nonce, method, payload = {}) => {
    
    let destination = url;
    let data;
    
    if(method === 'GET'){
      const keys = Object.keys(payload);
      destination += `?${keys[0]}=${payload[keys[0]]}`;
      keys.forEach((item,index) => {
        if(index !== 0){
          destination += `&${item}=${payload[item]}`
        }
      });
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

    this.connect();
  }

  connect() {
    if (this.socket) {
      this.socket.close();
    }

    this.socket = new WebSocket(this.url);

    this.socket.onopen = () => {
      this.connected = true;
      this.errorListeners.forEach((listener) => listener(null));
      this.connectListeners.forEach((listener) => listener());
      if (this.reconnectTimeout) clearTimeout(this.reconnectTimeout);
    };

    this.socket.onmessage = (event) => {
      try {
        const data = JSON.parse(event.data);
        this.messageListeners.forEach((listener) => listener(data));
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
      this.reconnect();
    };
  }

  reconnect() {
    if (this.reconnectTimeout) clearTimeout(this.reconnectTimeout);
    this.reconnectTimeout = setTimeout(this.connect.bind(this), this.reconnectInterval);
  }

  send(data) {
    if (this.socket && this.connected) {
      this.socket.send(JSON.stringify(data));
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

  close(){
    if(this.socket){
      this.socket.close();
    }
    if(this.reconnectTimeout) clearTimeout(this.reconnectTimeout);
  }
}