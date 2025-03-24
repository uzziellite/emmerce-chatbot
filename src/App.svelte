<script>
  import {onMount} from "svelte";
  import {sendRequestToApi, getGreeting} from "./lib/helper.js";

  let nonce = emmerceChatbot.nonce;
  const clientId = emmerceChatbot.clientId;
  const chatHandler = emmerceChatbot.chatHandler;
  let isOpen = $state( emmerceChatbot.isOpen ? emmerceChatbot.isOpen : false );
  let chatButtonColor = $state(null);
  let chatButtonHover = $state(null);
  let chatButtonPingColor = $state(null);
  let business_name = "Our website";
  let greetings = getGreeting();
  let messages = $state([]);
  
  let chatStarted = $state(false);
  let showChatStatus = $state(false);
  let conversation = $state('');
  let name = $state("");
  let email = $state("");
  let phone = $state("");
  let errors = {};
  let initialChat;
  const position = emmerceChatbot.position;
  const endpoint = emmerceChatbot.ajaxurl;
  const positionPrefix = position === 'right' ? 'r' : 'l';
  const chatTitle = emmerceChatbot.title ? emmerceChatbot.title : "Emmerce Chat";
  let inputElement = $state(null);
  let chatContainer = $state(null);
  let userData = $state(null);

  /**
   * Handle dynamic updates
   */
  $effect(() => {
    if (chatContainer && messages.length > 0) {
      chatContainer.scrollTop = chatContainer.scrollHeight - chatContainer.clientHeight;
    }
    
    userData = {
      "name":name,
      "email":email,
      "phone":phone,
      "session_active": true,
      "session_start": new Date().getTime()
    };

    initialChat = {
      "content":`${greetings} ${name}. Welcome to ${business_name}. How can I assist you today?`,
      "from_bot": true
    };

    /*setInterval(() => {
      const now = new Date().getTime();
      const span = now - userData.session_start;
      if(span > 5000) {
        chatStarted = false;
        showChatStatus = true;
      }
    },1000);*/
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
   * Get user data so that you can tell who you are chatting with
   * Enables the Emmerce App Subscriber to tell who it is they are talking to
   */
  const submitForm = (event) => {
    event.preventDefault();
    if (!name.trim()) errors.name = 'Name is required.';
    if (!/^\S+@\S+\.\S+$/.test(email)) errors.email = 'Invalid email address.';
    if (!/^\d{10}$/.test(phone)) errors.phone = 'Phone must be 10 digits.';
    if(errors.name || errors.phone || errors.email) return;


    localStorage.setItem('user_data', JSON.stringify(userData));
    chatStarted = true;
    messages.push(initialChat);

    const audio = new Audio(emmerceChatbot.snapSound);
    audio.play();
  }

  /**
   * Send Message to the backend for processing and storage
  */
  const sendMessage = () => {
    if(conversation.trim() === ""){
      return;
    }

    const data = {
      "content": conversation,
      "from_bot": false
    }

    messages = [...messages, data];
    conversation = "";

    const audio = new Audio(emmerceChatbot.popSound);
    audio.play();
  }

  onMount(async () => {
    /**
     * Fetch Color labels
     */
     await sendRequestToApi(endpoint, `${emmerceChatbot.accessUrl}/waba/labels/${clientId}`, nonce,'GET')
    .then(apiResponse => {
      const getChatColor = (data) => {
        for (let i = 0; i < data.length; i++) {
          if (data[i].label_name === "ChatButton") {
            chatButtonColor = data[i].color;
          } else if(data[i].label_name === "ChatButtonHover"){
            chatButtonHover = data[i].color;
          } else if(data[i].label_name === "ChatButtonPingColor"){
            chatButtonPingColor = data[i].color;
          }
        }
        return '#000000';
      }

      getChatColor(apiResponse);
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

      if(data.session_active){
        chatStarted = true;
        showChatStatus = false;
      }else{
        showChatStatus = true;
        chatStarted = false;
      }
    }

  })
</script>

<div class={`emc:fixed emc:bottom-0 emc:right-1 emc:mb-4 emc:mr-4 emc:z-50`}>
  {#if !isOpen}
    <button 
      style={`background-color: ${chatButtonColor};`} 
      onclick={() => isOpen = !isOpen} 
      class={`emc:relative emc:text-white emc:py-2 emc:px-4 emc:rounded-4xl emc:transition emc:duration-300 emc:flex emc:items-center emc:space-x-2 emc:cursor-pointer`}>
      <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="emc:size-6 emc:animate-wiggle">
        <path stroke-linecap="round" stroke-linejoin="round" d="M8.625 9.75a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H8.25m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H12m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0h-.375m-13.5 3.01c0 1.6 1.123 2.994 2.707 3.227 1.087.16 2.185.283 3.293.369V21l4.184-4.183a1.14 1.14 0 0 1 .778-.332 48.294 48.294 0 0 0 5.83-.498c1.585-.233 2.708-1.626 2.708-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0 0 12 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018Z" />
      </svg>
      <span class="emc:text-[16px] emc:font-semibold emc:font-[Inter]">Chat With Us</span>

      <!-- Pinging Dot -->
      <span class="emc:absolute emc:bottom-0 emc:right-0 emc:-mb-1 emc:mr-2 emc:flex emc:h-3 emc:w-3">
          <span class="emc:animate-ping emc:absolute emc:inline-flex emc:h-full emc:w-full emc:rounded-full emc:opacity-75" style={`background-color:${chatButtonPingColor};`}></span>
          <span class="emc:relative emc:inline-flex emc:rounded-full emc:h-3 emc:w-3" style={`background-color:${chatButtonPingColor};`}></span>
      </span>
    </button>
  {:else}
    <!-- Active Chat Indicator -->
    <button class="emc:flex emc:items-center emc:bg-white/60 emc:backdrop-blur-md emc:border emc:border-gray-200 emc:shadow-lg emc:px-4 emc:py-2 emc:rounded-full emc:space-x-3 emc:cursor-pointer" onclick={() => isOpen = false}>
      <!-- Glowing Active Dot -->
      <span class="emc:relative emc:flex emc:h-3 emc:w-3">
          <span class="emc:animate-ping emc:absolute emc:inline-flex emc:h-full emc:w-full emc:rounded-full emc:bg-green-500 emc:opacity-75"></span>
          <span class="emc:relative emc:inline-flex emc:rounded-full emc:h-3 emc:w-3 emc:bg-green-500"></span>
      </span>
      <!--Arrow pointing up-->
      <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="emc:size-6">
        <path stroke-linecap="round" stroke-linejoin="round" d="M11.99 7.5 8.24 3.75m0 0L4.49 7.5m3.75-3.75v16.499h11.25" />
      </svg>
      <span class="emc:text-gray-700 emc:font-medium emc:font-[Inter] emc:text-[16px]">
        {chatTitle}
      </span>
      <!-- Close Button -->
      <svg xmlns="http://www.w3.org/2000/svg" class="emc:size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
      </svg>
    </button>
  {/if}
</div>

{#if isOpen}
  <div class={`emc:fixed emc:bottom-20 emc:right-0 emc:sm:right-4 emc:w-full emc:sm:w-96 emc:z-[9999] emc:pr-0 emc:sm:pr-2`}>
    <div class="emc:bg-white/80 emc:backdrop-blur-md emc:shadow-2xl emc:rounded-lg emc:sm:max-w-lg emc:w-full emc:mx-2 emc:sm:mx-auto">
        <div 
          class="emc:px-4 emc:py-2 emc:border-b emc:text-white emc:rounded-t-lg emc:flex emc:justify-between emc:items-center"
          style={`background-color: ${chatButtonColor};`}>
            <a 
              class="emc:text-lg emc:font-semibold emc:no-underline"
              href="https://emmerce.io"
              target="_blank">{chatTitle}</a>
            <button 
              aria-label="Close" 
              class="emc:text-gray-300 emc:hover:text-white emc:focus:outline-none emc:focus:text-gray-400 emc:cursor-pointer" 
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
              {#each messages as message}
                {#if message.from_bot}
                  <p class={`from-emmerce emc:mb-2 emc:mt-2 emc:text-[16px] font-[Inter]`}>
                    {message.content}
                  </p>
                {:else}
                  <p class={`from-client emc:mb-2 emc:mt-2 emc:text-[16px]`}>
                    {message.content}
                  </p>
                {/if}
              {/each}
            </div>
          </div>
        {:else if !chatStarted && showChatStatus}
          <div class="emc:flex emc:justify-center emc:items-center emc:h-72 emc:overflow-y-auto">
            <div class="emc:py-2 emc:px-4 emc:w-full">
              <h2 class="emc:text-md emc:text-gray-700 emc:text-center emc:mb-4">Welcome Back, {name}</h2>
              <div class="emc:flex  emc:justify-center emc:items-center emc:space-x-4">
                <button class="emc:p-2 emc:mb-4 emc:bg-stone-900 emc:rounded-lg emc:text-md emc:text-white emc:cursor-pointer">
                  Load Chats
                </button>
                <button class="emc:p-2 emc:mb-4 emc:bg-blue-500 emc:text-white emc:rounded-lg emc:text-md emc:cursor-pointer" onclick={submitForm}>
                  Start New Chat
                </button>
              </div>
            </div>
          </div>
        {:else}
          <div class="emc:flex emc:justify-center emc:items-center">
            <div class="emc:py-2 emc:px-4 emc:w-full">
              <h2 class="emc:font-semibold emc:text-gray-700 emc:text-center emc:mb-4 emc:text-[18px]">Customer Information</h2>
      
              <form onsubmit={submitForm} class="emc:space-y-4">
                  <!-- Name -->
                  <div>
                      <label for="name" class="emc:block emc:text-gray-600 emc:font-medium emc:text-[16px]">Full Name</label>
                      <input id="name" type="text" bind:value={name} required
                          class="emc:w-full emc:p-1 emc:border emc:border-gray-300 emc:rounded-lg emc:focus:ring-2 emc:focus:ring-blue-400 emc:focus:outline-none emc:transition emc:text-[16px]" 
                          placeholder="John Doe" />
                      {#if errors.name}
                        <p class="emc:text-red-500 emc:text-[14px]">
                          {errors.name}
                        </p>
                      {/if}
                  </div>
      
                  <!-- Email -->
                  <div>
                      <label for="email" class="emc:block emc:text-gray-600 emc:font-medium emc:text-[16px]">Email Address</label>
                      <input id="email" type="email" bind:value={email} required
                          class="emc:w-full emc:p-1 emc:border emc:border-gray-300 emc:rounded-lg emc:focus:ring-2 emc:focus:ring-blue-400 emc:focus:outline-none emc:transition emc:text-[16px]" 
                          placeholder="john@example.com" />
                      {#if errors.email}
                        <p class="emc:text-red-500 emc:text-[14px]">
                          {errors.email}
                        </p>
                      {/if}
                  </div>
      
                  <!-- Phone -->
                  <div>
                      <label class="emc:block emc:text-gray-600 emc:font-medium emc:text-[16px]" for="phone">Phone Number</label>
                      <input id="phone" type="tel" bind:value={phone} required
                          class="emc:w-full emc:p-1 emc:border emc:border-gray-300 emc:rounded-lg emc:focus:ring-2 emc:focus:ring-blue-400 emc:focus:outline-none emc:transition emc:text-[16px]" 
                          placeholder="+1 234 567 8901" />
                      {#if errors.phone}
                        <p class="emc:text-red-500 emc:text-[14px]">
                          {errors.phone}
                        </p>
                      {/if}
                  </div>
      
                  <!-- Submit Button -->
                  <button 
                    type="submit"
                    class="emc:w-full emc:text-white emc:font-semibold emc:py-1 emc:rounded-lg emc:transition emc:duration-300 emc:text-[16px] emc:cursor-pointer"
                    style={`background-color:${chatButtonColor};`}>
                      Start Chat
                  </button>
              </form>
          </div>
          </div>          
        {/if}
        {#if chatStarted}
          <div class="emc:p-6 emc:border-t emc:flex">
              <input 
                type="text" 
                placeholder="Type a message" 
                class="emc:w-full emc:px-3 emc:py-2 emc:border emc:rounded-l-md emc:focus:outline-none emc:focus:ring emc:focus:ring-blue-500 emc:leading-none emc:text-[16px]" 
                bind:value={conversation}
                onkeydown={handleKeyDown}
                bind:this={inputElement}>
              <button 
                class="emc:text-white emc:px-4 emc:py-2 emc:rounded-r-md emc:hover:bg-blue-600 emc:transition emc:duration-300 emc:leading-none" 
                onclick={sendMessage}
                style={`background-color: ${chatButtonColor};`}>Send</button>
          </div>
        {/if}
    </div>
  </div>
{/if}
