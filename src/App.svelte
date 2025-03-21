<script>
  import {onMount} from "svelte";
  import {sendRequestToApi} from "./lib/helper.js";

  let nonce;
  let isOpen = emmerceChatbot.isOpen ? emmerceChatbot.isOpen : false;
  let colors = [];
  let business_name = "Our website";
  let messages = [
    {
      "content":`Welcome to ${business_name}. How can I assist you today?`,
      "from_bot": true
    },
    {
      "content":`I need a blender`,
      "from_bot": false
    },
    {
      "content":`Do you have a specific design in mind?`,
      "from_bot": true
    }
  ];
  let chatStarted = false;
  let name = "";
  let email = "";
  let phone = "";
  let errors = {};
  const position = emmerceChatbot.position;
  const endpoint = emmerceChatbot.ajaxurl;
  const positionPrefix = position === 'right' ? 'r' : 'l';
  const chatTitle = emmerceChatbot.title ? emmerceChatbot.title : "Emmerce Chat";

  /**
   * Get user data so that you can tell who you are chatting with
   * Enables the Emmerce App Subscriber to tell who it is they are talking to
   */
  const submitForm = () => {
    if (!name.trim()) errors.name = 'Name is required.';
    if (!/^\S+@\S+\.\S+$/.test(email)) errors.email = 'Invalid email address.';
    if (!/^\d{10}$/.test(phone)) errors.phone = 'Phone must be 10 digits.';
    if(errors.name || errors.phone || errors.email) return;

    const userData = {
      "name":name,
      "email":email,
      "phone":phone
    };

    localStorage.setItem('user_data', JSON.stringify(userData));
    chatStarted = true;
  }

  onMount(() => {
    /**
     * Log on the terminal that Chat is ready
    */
    console.info(`Emmerce Chatbot Mounted. Ready for service!`)
    nonce = document.getElementById('emmerce-chat-nonce')?.value;
    if (!nonce) {
      console.error('Nonce not found.');
    }

    /**
     * If customer records already exist, proceed to the chat window.
     */
    if( localStorage.getItem('user_data') ) {
      chatStarted = true;
    }

    
    const params = {
      "start_date": "2025-03-01",
      "end_date": "2025-03-31",
      "client": 49
    }

    sendRequestToApi(endpoint, 'https://demoinfinity.emmerce.io/api/v1/dashboard/filter/view/', nonce,'POST', params)
    .then(apiResponse => {
      console.log('API Response:', apiResponse);
    })
    .catch(error => {
      console.error('Error:', error);
    });

  })
</script>

<div class={`emc:fixed emc:bottom-0 emc:right-1 emc:mb-4 emc:mr-4 emc:z-50`}>
  {#if !isOpen}
    <button on:click={() => isOpen = !isOpen} class="emc:relative emc:bg-blue-500 emc:text-white emc:py-2 emc:px-4 emc:rounded-4xl emc:hover:bg-blue-600 emc:transition emc:duration-300 emc:flex emc:items-center emc:space-x-2">
      <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="emc:size-6 emc:animate-wiggle">
        <path stroke-linecap="round" stroke-linejoin="round" d="M8.625 9.75a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H8.25m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H12m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0h-.375m-13.5 3.01c0 1.6 1.123 2.994 2.707 3.227 1.087.16 2.185.283 3.293.369V21l4.184-4.183a1.14 1.14 0 0 1 .778-.332 48.294 48.294 0 0 0 5.83-.498c1.585-.233 2.708-1.626 2.708-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0 0 12 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018Z" />
      </svg>
      <span>Chat With Us</span>

      <!-- Pinging Dot -->
      <span class="emc:absolute emc:bottom-0 emc:right-0 emc:-mb-1 emc:mr-2 emc:flex emc:h-3 emc:w-3">
          <span class="emc:animate-ping emc:absolute emc:inline-flex emc:h-full emc:w-full emc:rounded-full emc:bg-red-500 emc:opacity-75"></span>
          <span class="emc:relative emc:inline-flex emc:rounded-full emc:h-3 emc:w-3 emc:bg-red-500"></span>
      </span>
    </button>
  {:else}
    <!-- Active Chat Indicator -->
    <button class="emc:flex emc:items-center emc:bg-white/60 emc:backdrop-blur-md emc:border emc:border-gray-200 emc:shadow-lg emc:px-4 emc:py-2 emc:rounded-full emc:space-x-3 emc:cursor-pointer" on:click={() => isOpen = false}>
      <!-- Glowing Active Dot -->
      <span class="emc:relative emc:flex emc:h-3 emc:w-3">
          <span class="emc:animate-ping emc:absolute emc:inline-flex emc:h-full emc:w-full emc:rounded-full emc:bg-green-500 emc:opacity-75"></span>
          <span class="emc:relative emc:inline-flex emc:rounded-full emc:h-3 emc:w-3 emc:bg-green-500"></span>
      </span>
      <!--Arrow pointing up-->
      <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="emc:size-6">
        <path stroke-linecap="round" stroke-linejoin="round" d="M11.99 7.5 8.24 3.75m0 0L4.49 7.5m3.75-3.75v16.499h11.25" />
      </svg>
      <span class="emc:text-gray-700 emc:font-medium">
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
  <div class={`emc:fixed emc:bottom-20 emc:right-4 emc:w-96 emc:z-[9999] emc:pr-2`}>
    <div class="emc:bg-white/80 emc:backdrop-blur-md emc:shadow-2xl emc:rounded-lg emc:max-w-lg emc:w-full">
        <div class="emc:px-4 emc:py-2 emc:border-b emc:bg-blue-500 emc:text-white emc:rounded-t-lg emc:flex emc:justify-between emc:items-center">
            <p class="emc:text-lg emc:font-semibold">{chatTitle}</p>
            <button aria-label="Close" class="emc:text-gray-300 emc:hover:text-white emc:focus:outline-none emc:focus:text-gray-400" on:click={() => isOpen = !isOpen}>
                <svg xmlns="http://www.w3.org/2000/svg" class="emc:w-6 emc:h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        <!--Messages are displayed here-->
        {#if chatStarted}
        
        <div class="emc:p-4 emc:h-80 emc:overflow-y-auto">
          <div class="imessage">
            {#each messages as message, index}
              {#if message.from_bot}
                <p class={`from-emmerce emc:float-left emc:mb-2`}>
                  {message.content}
                </p>
              {:else}
                <p class={`from-client emc:float-right emc:mb-2`}>
                  {message.content}
                </p>
              {/if}
            {/each}
          </div>
        </div>
        {:else}
          <div class="emc:flex emc:justify-center emc:items-center emc:bg-gray-100">
            <div class="emc:bg-white/70 emc:backdrop-blur-md emc:shadow-lg emc:py-2 emc:px-4 emc:w-full">
              <h2 class="emc:font-semibold emc:text-gray-700 emc:text-center emc:mb-4">Customer Information</h2>
      
              <form on:submit|preventDefault={submitForm} class="emc:space-y-4">
                  <!-- Name -->
                  <div>
                      <label for="name" class="emc:block emc:text-gray-600 emc:font-medium">Full Name</label>
                      <input id="name" type="text" bind:value={name} required
                          class="emc:w-full emc:p-1 emc:border emc:border-gray-300 emc:rounded-lg emc:focus:ring-2 emc:focus:ring-blue-400 emc:focus:outline-none emc:transition" 
                          placeholder="John Doe" />
                      {#if errors.name}
                        <p class="emc:text-red-500">
                          {errors.name}
                        </p>
                      {/if}
                  </div>
      
                  <!-- Email -->
                  <div>
                      <label for="email" class="emc:block emc:text-gray-600 emc:font-medium">Email Address</label>
                      <input id="email" type="email" bind:value={email} required
                          class="emc:w-full emc:p-1 emc:border emc:border-gray-300 emc:rounded-lg emc:focus:ring-2 emc:focus:ring-blue-400 emc:focus:outline-none emc:transition" 
                          placeholder="john@example.com" />
                      {#if errors.email}
                        <p class="emc:text-red-500">
                          {errors.email}
                        </p>
                      {/if}
                  </div>
      
                  <!-- Phone -->
                  <div>
                      <label class="emc:block emc:text-gray-600 emc:font-medium" for="phone">Phone Number</label>
                      <input id="phone" type="tel" bind:value={phone} required
                          class="emc:w-full emc:p-1 emc:border emc:border-gray-300 emc:rounded-lg emc:focus:ring-2 emc:focus:ring-blue-400 emc:focus:outline-none emc:transition" 
                          placeholder="+1 234 567 8901" />
                      {#if errors.phone}
                        <p class="emc:text-red-500">
                          {errors.phone}
                        </p>
                      {/if}
                  </div>
      
                  <!-- Submit Button -->
                  <button type="submit"
                      class="emc:w-full emc:bg-blue-500 emc:text-white emc:font-semibold emc:py-1 emc:rounded-lg emc:hover:bg-blue-600 emc:transition emc:duration-300">
                      Start Chat
                  </button>
              </form>
          </div>
          </div>          
        {/if}
        {#if chatStarted}
          <div class="emc:p-6 emc:border-t emc:flex">
              <input id="user-input" type="text" placeholder="Type a message" class="emc:w-full emc:px-3 emc:py-2 emc:border emc:rounded-l-md emc:focus:outline-none emc:focus:ring-2 emc:focus:ring-blue-500">
              <button id="send-button" class="emc:bg-blue-500 emc:text-white emc:px-4 emc:py-2 emc:rounded-r-md emc:hover:bg-blue-600 emc:transition emc:duration-300">Send</button>
          </div>
        {/if}
    </div>
  </div>
{/if}
