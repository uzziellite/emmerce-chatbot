<script>
  import {onMount} from "svelte";
  import {sendRequestToApi, WebSocketManager, validatePhoneNumber} from "./lib/helper.js";
  import {ChatSessionDB} from "./lib/db.js";
  import './chat.css';

  let nonce = emmerceChatbot.nonce;
  const clientId = emmerceChatbot.clientId;
  let isOpen = $state( emmerceChatbot.isOpen ? emmerceChatbot.isOpen : false );
  let chatButtonColor = $state(null);
  let loading = $state(false);
  let chatButtonHover = $state(null);
  let chatButtonPingColor = $state(null);
  let messages = $state([]);
  let chatSettings = $state({});
  let chatStarted = $state(false);
  let showChatStatus = $state(false);
  let conversation = $state('');
  let name = $state("");
  let email = $state("");
  let phone = $state("");
  let errors = $state({});
  let initialChat;
  const position = emmerceChatbot.position;
  const endpoint = emmerceChatbot.ajaxurl;
  let inputElement = $state(null);
  let chatContainer = $state(null);
  let userData = $state(null);
  const chatSessionDB = new ChatSessionDB();
  let sessionKey = $state(null);
  let websocketStr = $state("");
  let websocket = $state(null);
  let chatSessionIsActive = $state(false);
  let retryCount = 0;
  const maxRetries = 10;
  let isSending = $state(false);
  let chatDisconnected = $state(false);
  let firstLoading = $state(true);

  /**
   * Handle dynamic updates
   */
  $effect(async () => {
    if (chatContainer && messages.length > 0) {
      chatContainer.scrollTop = chatContainer.scrollHeight - chatContainer.clientHeight;
    }
    
    userData = {
      "name":name,
      "email":email,
      "phone":phone
    };

    initialChat = {
      "content":`Hello`,
      "from_bot": false
    };

    if( sessionKey ){
      websocketStr = `${emmerceChatbot.ws}/website-conversation/${sessionKey}/?api_key=${emmerceChatbot.api_key}`
      websocket = new WebSocketManager(websocketStr, { reconnectInterval: 5000 });
    }

    if(! firstLoading && ! chatSessionIsActive && chatStarted && !loading){
      terminateChat();
    }

  });

  /**
   * Handle the enter Key to send message to the customer as need be
  */
  const handleKeyDown = (event) => {
    if (event.key === 'Enter') {
      sendMessage();
    }
  }

  /**
   * Manage chat session
  */
  const manageChatSession = async (sessionId, newMessage) => {
    try {
      let session = await chatSessionDB.getSession(sessionId);
  
      if (!session) {
        session = { sessionId, messages: [], timestamp: Date.now() };
      }
  
      if (newMessage) {
        session.messages.push(newMessage);
        session.timestamp = Date.now();
      }
  
      await chatSessionDB.storeSession(session);
      //console.log('Session updated:', session);
  
    } catch (error) {
      console.error('Error managing chat session:', error);
    }
  }

  /**
   * Delete chat session
  */
  const deleteChatSession = async (sessionId) => {
    try {
      await chatSessionDB.deleteDatabase(sessionId);
      //console.log('Session deleted:', sessionId);
    } catch (error) {
      console.error('Error deleting chat session:', error);
    }
  }

  /**
   * Get user data so that you can tell who you are chatting with
   * Enables the Emmerce App Subscriber to tell who it is they are talking to
   */
  const submitForm = (event) => {
    event.preventDefault();
    if (!name.trim()) errors.name = 'Name is required.';
    if (!/^\S+@\S+\.\S+$/.test(email)) errors.email = 'Invalid email address.';
    if (!validatePhoneNumber(phone)) errors.phone = 'Please enter a valid phone number';
    if(errors.name || errors.phone || errors.email) return;

    const params = {
      "customer_name": name,
      "customer_email": email,
      "customer_phone": phone,
      "client_id": clientId
    }

    loading = true;

    sendRequestToApi(endpoint, `${emmerceChatbot.accessUrl}/waba/send-website-message/${clientId}/`, nonce,'POST', params)
    .then(apiResponse => {
      
      loading = false;
      userData.session_start = apiResponse.data?.created_at;
      userData.session_id = apiResponse.data?.conversation?.session_id;
      
      if( userData.session_id ){
        
        userData.session_active = true;
        localStorage.setItem('user_data', JSON.stringify(userData));
        chatStarted = true;
        showChatStatus = false;
        messages.push(initialChat);
        manageChatSession(userData.session_id, initialChat);
        
        const audio = new Audio(emmerceChatbot.snapSound);
        audio.play();

      }
    })
    .catch(error => {
      console.error('Error:', error);
    });


  }

  /**
   * Send Message to the backend for processing and storage
  */
  const sendMessage = () => {
    if(conversation.trim() === ""){
      return;
    }
    
    isSending = true;
    
    const data = {
      "content": conversation,
      "from_bot": false
    }
    messages = [...messages, data];
    
    const params = {
      "session_id": sessionKey,
      "message": conversation,
      "sender": "customer",
      "message_type": "text"
    }
    
    conversation = "";
    
    sendRequestToApi(endpoint, `${emmerceChatbot.accessUrl}/waba/send-website-message/${clientId}/`, nonce, 'POST', params)
    .then(apiResponse => {
      console.log(apiResponse)
      if(apiResponse.data?.status === "sent"){
        isSending = false;
        manageChatSession(sessionKey, data);
        const audio = new Audio(emmerceChatbot.popSound);
        audio.play();
        retryCount = 0;
        firstLoading = false;
      } else if(retryCount < maxRetries){
        //Chat was not sent, resend the message
        retryCount++;
        sendMessage();
      } else {
        console.error("Message not sent after multiple retries");
        isSending = false;
        messages.pop();
        firstLoading = false;
      }
    })
    .catch(error => {
      console.error('Error:', error);
    });
  }

  /**
   * Get the chat transcript
  */
  const getTranscript = async(sessionID) => {
    try{
        return await chatSessionDB.getSession(sessionID);
    } catch(error){
        console.error("Error retrieving transcript: ", error);
    }
  }

  /**
   * Check session validity
  */
  const checkChatSessionValidity = async(sessionId) => {
    return await sendRequestToApi(endpoint, `${emmerceChatbot.accessUrl}/waba/website-check-session-validity/${sessionId}/${clientId}`, nonce,'GET');
  }

  /**
   * Client terminate chat session
  */
  const terminateChat = async () => {
    loading = true;
    localStorage.removeItem('user_data');
    deleteChatSession(sessionKey);
    name = "";
    email = "";
    phone = "";
    messages = [];

    await sendRequestToApi(endpoint, `${emmerceChatbot.accessUrl}/waba/website-client-resolve-session/${sessionKey}/${clientId}`, nonce,'POST')
    .then(apiResponse => {
      console.log(apiResponse)
      chatSessionIsActive = false;
      chatStarted = false;
      showChatStatus = false;
      loading = false;
    })
    .catch(error => {
      console.error('Error:', error);
      loading = false;
    });
  }

  onMount(async () => {
    /**
     * Fetch Chat Settings
     */
     await sendRequestToApi(endpoint, `${emmerceChatbot.accessUrl}/clients/client-website-settings/${clientId}`, nonce,'GET')
    .then(apiResponse => {
      console.log(apiResponse)
      chatSettings = apiResponse;
      chatButtonColor = chatSettings?.website_color
      chatButtonHover = chatSettings?.website_color
      chatButtonPingColor = chatSettings?.website_color
      
    })
    .catch(error => {
      console.error('Error:', error);
    });
    /**
     * Log on the terminal that Chat is ready
    */
    console.info(`Emmerce Chatbot Mounted. Ready for service!`)

    /**
     * If customer records already exist, proceed to the chat window.
     * However ensure that chat session is active before showing messages.
     */
    if( localStorage.getItem('user_data') ) {
      const data = JSON.parse( localStorage.getItem('user_data') );
      name = data.name;
      email = data.email;
      phone = data.phone;
      sessionKey = data.session_id;
      const chatStatus =  await checkChatSessionValidity(sessionKey);
      chatSessionIsActive = chatStatus?.status;

      if(chatStatus?.status){
        chatStarted = true;
        showChatStatus = false;
        const transcript = await getTranscript(sessionKey);
        messages = transcript.messages;
        firstLoading = false;
      }else{
        showChatStatus = false;
        chatStarted = false;
        terminateChat();
      }

    }

    //set interval to check if chat session is still active
    setInterval(async () => {
      if( chatSessionIsActive ){
        const chatStatus =  await checkChatSessionValidity(sessionKey);
        chatSessionIsActive = chatStatus?.status;
      }
    }, 10000);

    /**
     * Handle websocket messages when the socket is ready
    */
    if(websocket){

      websocket.addMessageListener((message) => {
        if(message.sender == 'business'){
          const length = messages.length - 1;
          
          //Avoid duplicate messages
          if(messages[length].content === message.message){
            return;
          }

          const data = {
            "content": message.message,
            "from_bot": true
          }
          messages = [...messages, data];

          manageChatSession(sessionKey, data);
          
          const audio = new Audio(emmerceChatbot.snapSound);
          audio.play();

        }
      });
  
      websocket.addErrorListener((error) => {
        console.error('WebSocket error:', error);
      });
  
      websocket.addConnectListener(() => {
          console.log("websocket connected");
          chatDisconnected = false;
      });
  
      websocket.addDisconnectListener(() => {
          //console.log("websocket disconnected");
          chatDisconnected = true;
      });
    }
  });
</script>

<div class={`emc:fixed emc:bottom-0 emc:mb-4 emc:z-50 ${position === 'right' ? 'emc:right-1 emc:mr-4' : 'emc:left-1 emc:ml-4'}`}>
  {#if !isOpen}
    <button 
      style={`background-color: ${chatButtonColor};`} 
      onclick={() => isOpen = !isOpen} 
      class={`emc:relative emc:text-white emc:py-2 emc:px-4 emc:rounded-4xl emc:transition emc:duration-300 emc:flex emc:items-center emc:space-x-2 emc:cursor-pointer emc:leading-none`}>
      <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="emc:size-6 emc:animate-wiggle">
        <path stroke-linecap="round" stroke-linejoin="round" d="M8.625 9.75a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H8.25m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H12m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0h-.375m-13.5 3.01c0 1.6 1.123 2.994 2.707 3.227 1.087.16 2.185.283 3.293.369V21l4.184-4.183a1.14 1.14 0 0 1 .778-.332 48.294 48.294 0 0 0 5.83-.498c1.585-.233 2.708-1.626 2.708-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0 0 12 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018Z" />
      </svg>
      {#if chatSettings?.call_to_action_text}
        <span class="emc:text-[16px] emc:font-semibold emc:font-[Inter]">
          {chatSettings.call_to_action_text}
        </span>
      {/if}

      <!-- Pinging Dot -->
      <span class="emc:absolute emc:bottom-0 emc:right-0 emc:-mb-1 emc:mr-2 emc:flex emc:h-3 emc:w-3">
          <span class="emc:animate-ping emc:absolute emc:inline-flex emc:h-full emc:w-full emc:rounded-full emc:opacity-75" style={`background-color:${chatButtonPingColor};`}></span>
          <span class="emc:relative emc:inline-flex emc:rounded-full emc:h-3 emc:w-3" style={`background-color:${chatButtonPingColor};`}></span>
      </span>
    </button>
  {:else}
    <!-- Active Chat Indicator -->
    <button class="emc:flex emc:items-center emc:bg-white/60 emc:backdrop-blur-md emc:border emc:border-gray-200 emc:shadow-lg emc:px-4 emc:py-2 emc:rounded-full emc:space-x-3 emc:cursor-pointer emc:leading-none emc:z-[9999]" onclick={() => isOpen = false}>
      <!-- Glowing Active Dot -->
      <span class="emc:relative emc:flex emc:h-3 emc:w-3">
          <span class="emc:animate-ping emc:absolute emc:inline-flex emc:h-full emc:w-full emc:rounded-full emc:bg-green-500 emc:opacity-75"></span>
          <span class="emc:relative emc:inline-flex emc:rounded-full emc:h-3 emc:w-3 emc:bg-green-500"></span>
      </span>
      
      <span class="emc:text-gray-700 emc:font-medium emc:font-[Inter] emc:text-[16px]">
        {chatSettings.client_name}
      </span>
      <!-- Close Button -->
      <svg xmlns="http://www.w3.org/2000/svg" class="emc:size-6 emc:text-gray-700" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
      </svg>
    </button>
  {/if}
</div>

{#if isOpen}
  <div class={`emc:fixed emc:bottom-20 emc:w-full emc:sm:w-96 emc:z-[9999] ${position === 'right' ? 'emc:pr-0 emc:sm:pr-2 emc:right-0 emc:sm:right-4' : 'emc:pl-0 emc:sm:pl-2 emc:left-0 emc:sm:left-4'}`}>
    <div class="emc:bg-white/80 emc:backdrop-blur-md emc:shadow-2xl emc:rounded-lg emc:sm:max-w-lg emc:w-full emc:mx-2 emc:sm:mx-auto">
        <!--Main widget title-->
        <div 
          class="emc:px-4 emc:py-2 emc:border-b emc:text-white emc:rounded-t-lg emc:flex emc:justify-between emc:items-center"
          style={`background-color: ${chatButtonColor};`}>
            <div 
              class="emc:text-lg emc:font-semibold emc:no-underline"
              href="https://emmerce.io"
              target="_blank">
              <span class="emc:text-[22px]">
                {chatSettings.widget_title} <br>
              </span>
              <span class="emc:text-[18px]">
                {chatSettings.widget_description}
              </span>
            </div>
            <button 
              aria-label="Close" 
              class="emc:text-gray-300 emc:hover:text-white emc:focus:outline-none emc:focus:text-gray-400 emc:cursor-pointer emc:leading-none emc:bg-transparent emc:border-none" 
              onclick={() => isOpen = !isOpen}>
                <svg xmlns="http://www.w3.org/2000/svg" class="emc:w-6 emc:h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        <!--Messages are displayed here-->
        {#if chatStarted && !showChatStatus}
          <div class="emc:p-4 emc:h-80 emc:overflow-y-auto" bind:this={chatContainer}>
            <div class="imessage emc:flex emc:flex-col">
              {#each messages as message,index}
                {#if message.from_bot}
                  <p class={`from-emmerce emc:mb-2 emc:mt-2 emc:text-[16px] font-[Inter]`}>
                    {message.content}
                  </p>
                {:else}
                  <div class="emc:flex emc:flex-col emc:items-end">
                    <p class={`from-client emc:text-[16px]`}>
                      {message.content}
                    </p>
                    {#if index === messages.length - 1 && isSending}
                      <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="emc:w-4 emc:h-4 emc:text-gray-400">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                      </svg>
                    {:else}
                      <svg 
                        viewBox="0 0 24 24" 
                        fill="none" 
                        xmlns="http://www.w3.org/2000/svg" 
                        stroke="currentColor" 
                        class="emc:w-4 emc:h-4 emc:text-green-500"
                      >
                        <path 
                          d="M4 12.9L7.14286 16.5L15 7.5" 
                          stroke-width="1.5" 
                          stroke-linecap="round" 
                          stroke-linejoin="round"
                        />
                        <path 
                          d="M20 7.5625L11.4283 16.5625L11 16" 
                          stroke-width="1.5" 
                          stroke-linecap="round" 
                          stroke-linejoin="round"
                        />
                      </svg>
                    {/if}
                  </div>
                {/if}
              {/each}
            </div>
          </div>
        {:else if !chatStarted && showChatStatus}
          <div class="emc:flex emc:justify-center emc:items-center emc:h-72 emc:overflow-y-auto">
            <div class="emc:py-2 emc:px-4 emc:w-full">
              <h2 class="emc:text-md emc:text-gray-700 emc:text-center emc:mb-4 emc:text-[24px]">Welcome Back, {name}</h2>
              <div class="emc:flex  emc:justify-center emc:items-center emc:space-x-4">
                {#if loading}
                  <button 
                    class="emc:p-2 emc:mb-4 emc:text-white emc:rounded-lg emc:text-md emc:cursor-pointer emc:leading-none emc:text-[16px]"
                    style={`background-color:${chatButtonColor};`}>
                    Please wait ....
                  </button>
                {:else}
                  <button 
                    class="emc:p-2 emc:mb-4 emc:text-white emc:rounded-lg emc:text-md emc:cursor-pointer emc:leading-none emc:text-[16px]" onclick={submitForm}
                    style={`background-color:${chatButtonColor};`}>
                    Start Conversation
                  </button>
                {/if}
              </div>
            </div>
          </div>
        {:else}
          <div class="emc:flex emc:justify-center emc:items-center">
            <div class="emc:py-2 emc:px-4 emc:w-full">
              <h2 class="emc:font-semibold emc:text-center emc:mb-4 emc:text-[18px]">Customer Information</h2>
      
              <form onsubmit={submitForm} class="emc:space-y-4">
                  <!-- Name -->
                  <div>
                      <label for="name" class="emc:block emc:font-medium emc:text-[16px] emc:mb-0">Full Name</label>
                      <input id="name" type="text" bind:value={name} required
                          class="emc:w-full emc:p-1 emc:border emc:border-gray-300 emc:rounded-lg emc:focus:ring-2 emc:focus:ring-blue-400 emc:focus:outline-none emc:transition emc:text-[16px] emc:text-black emc:mb-0" 
                          placeholder="John Doe" />
                      {#if errors.name}
                        <p class="emc:text-red-500 emc:text-[14px]">
                          {errors.name}
                        </p>
                      {/if}
                  </div>
      
                  <!-- Email -->
                  <div>
                      <label for="email" class="emc:block emc:font-medium emc:text-[16px] emc:mb-0">Email Address</label>
                      <input id="email" type="email" bind:value={email} required
                          class="emc:w-full emc:p-1 emc:border emc:border-gray-300 emc:rounded-lg emc:focus:ring-2 emc:focus:ring-blue-400 emc:focus:outline-none emc:transition emc:text-[16px] emc:mb-0" 
                          placeholder="john@example.com" />
                      {#if errors.email}
                        <p class="emc:text-red-500 emc:text-[14px]">
                          {errors.email}
                        </p>
                      {/if}
                  </div>
      
                  <!-- Phone -->
                  <div>
                      <label class="emc:block emc:font-medium emc:text-[16px] emc:mb-0" for="phone">Phone Number</label>
                      <input id="phone" type="tel" bind:value={phone} required
                          class="emc:w-full emc:p-1 emc:border emc:border-gray-300 emc:rounded-lg emc:focus:ring-2 emc:focus:ring-blue-400 emc:focus:outline-none emc:transition emc:text-[16px] emc:mb-0" 
                          placeholder="+1 234 567 8901" />
                      {#if errors.phone}
                        <p class="emc:text-red-500 emc:text-[14px]">
                          {errors.phone}
                        </p>
                      {/if}
                  </div>
      
                  <!-- Submit Button -->
                  {#if loading}
                    <button 
                      type="submit"
                      class="emc:w-full emc:text-white emc:font-semibold emc:py-2 emc:rounded-lg emc:transition emc:duration-300 emc:text-[16px] emc:cursor-pointer emc:leading-none"
                      style={`background-color:${chatButtonColor};`}>
                        Please wait ....
                    </button>
                  {:else}
                    <button 
                    type="submit"
                    class="emc:w-full emc:text-white emc:font-semibold emc:py-2 emc:rounded-lg emc:transition emc:duration-300 emc:text-[16px] emc:cursor-pointer emc:leading-none emc:mb-0"
                    style={`background-color:${chatButtonColor};`}>
                      Start Chat
                  </button>
                {/if}
              </form>
          </div>
          </div>          
        {/if}
        {#if chatStarted && !chatDisconnected}
          <div class="emc:px-6 emc:py-2 emc:border-t emc:flex">
              <input 
                type="text" 
                placeholder="Type a message" 
                class="emc:w-full emc:px-3 emc:py-2 emc:border emc:rounded-l-md emc:focus:outline-none emc:focus:ring emc:focus:ring-blue-500 emc:leading-none emc:text-[16px]" 
                bind:value={conversation}
                onkeydown={handleKeyDown}
                bind:this={inputElement}>
              <button 
                class={`emc:text-white emc:px-4 emc:py-2 emc:rounded-r-md emc:transition emc:duration-300 emc:leading-none emc:ring emc:cursor-pointer`} 
                onclick={sendMessage}
                style={`background-color: ${chatButtonColor};`}>Send</button>
          </div>
          <div class="emc:flex emc:align-center emc:justify-center emc:pb-1">
            {#if loading}
              <span class="emc:text-gray-500 emc:text-[14px]">
                Please wait ...
              </span>
            {:else}
              <button
                class="emc:text-black emc:cursor-pointer emc:leading-none emc:text-[14px] emc:text-underline emc:py-0 emc:mb-0"
                onclick={terminateChat}>
                  End Chat
              </button>
            {/if}
          </div>
        {:else}
          <div class="emc:flex emc:justify-center emc:items-center emc:pb-1">
            <span class="emc:text-red-500 emc:text-[14px]">
              Chat Disconnected
            </span>
          </div>
        {/if}
    </div>
  </div>
{/if}
