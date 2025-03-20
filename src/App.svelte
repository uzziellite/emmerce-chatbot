<script>
  import {onMount} from "svelte";

  let nonce;
  const position = emmerceChatbot.position;
  const endpoint = emmerceChatbot.ajaxurl;

  /**
   * 
   * @param payload A JSON payload
   * @param url The Address to connect to
   * @param nonce Wordpress Security Key
   * @param method Method that the API will accept
   */
  const sendChatMessage = (url, nonce, method, payload = {}) => {
    
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

  onMount(() => {
    console.info(`Emmerce Chatbot Mounted. Ready for service!`)
    nonce = document.getElementById('emmerce-chat-nonce')?.value;
    if (!nonce) {
      console.error('Nonce not found.');
    }

    /*
    Example Usage of the transporter function
    const params = {
      "start_date": "2025-03-01",
      "end_date": "2025-03-31",
      "client": 49
    }

    sendChatMessage('https://demoinfinity.emmerce.io/api/v1/dashboard/filter/view/', nonce,'POST', params)
    .then(apiResponse => {
      console.log('API Response:', apiResponse);
    })
    .catch(error => {
      console.error('Error:', error);
    });*/
  })
</script>