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