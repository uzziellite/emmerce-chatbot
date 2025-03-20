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
    return new Promise((resolve, reject) => {
      const formData = new FormData();
      const data = JSON.stringify(payload);
      
      formData.append('action', 'emmerce_chat_message');
      formData.append('message', data);
      formData.append('url', url);
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

    const params = {
      "page":2,
      "limit":5
    }

    sendChatMessage('https://demoinfinity.emmerce.io/api/v1/product/assortment/get_list/49', nonce,'GET')
    .then(apiResponse => {
      console.log('API Response:', apiResponse);
    })
    .catch(error => {
      console.error('Error:', error);
    });
  })
</script>