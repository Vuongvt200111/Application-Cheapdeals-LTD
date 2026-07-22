/* support.js — real-time support chat with AJAX, status loaders, and simulated CSR typing feedback (BUG-020) */
document.addEventListener('DOMContentLoaded', () => {
  const chatDiv = document.querySelector('.chat');
  const chatForm = document.querySelector('.chat-input');
  const msgInput = chatForm ? chatForm.querySelector('input[name="message"]') : null;
  const attachedInput = chatForm ? chatForm.querySelector('input[name="attached_package"]') : null;
  
  if (!chatDiv) return;
  
  // Scroll to bottom
  chatDiv.scrollTop = chatDiv.scrollHeight;
  
  let currentMsgCount = chatDiv.querySelectorAll('.bubble').length;
  let typingBubble = null;

  function showTypingIndicator() {
    if (typingBubble) return;
    typingBubble = document.createElement('div');
    typingBubble.className = 'bubble staff';
    typingBubble.style.cssText = 'margin-bottom:12px; padding:10px; border-radius:12px; max-width:75%; background:#e9ecef; color:#000; font-style:italic;';
    typingBubble.innerHTML = '<span class="who" style="font-size:11px; font-weight:bold; opacity:0.8;">CSR Representative</span><div class="msg-text" style="margin-top:4px;">CSR is typing<span class="typing-dots">...</span></div>';
    chatDiv.appendChild(typingBubble);
    chatDiv.scrollTop = chatDiv.scrollHeight;
  }

  function removeTypingIndicator() {
    if (typingBubble) {
      typingBubble.remove();
      typingBubble = null;
    }
  }

  // Poll for new messages
  function pollMessages() {
    fetch('support.php?action=get_messages')
    .then(res => res.json())
    .then(data => {
      if (data.messages) {
        const msgs = data.messages;
        if (msgs.length > currentMsgCount) {
          removeTypingIndicator();
          
          // Rebuild chat list
          let html = '';
          msgs.forEach(m => {
            const isUser = m.sender === 'user';
            const isProductLink = m.message.indexOf('[Liên kết sản phẩm]:') !== -1;
            let displayMsg = m.message;
            if (isProductLink) {
              displayMsg = m.message.replace('[Liên kết sản phẩm]:', '📦 *Product Inquiry:* ');
            }
            
            const alignment = isUser ? 'margin-left:auto; background:#007bff; color:#fff;' : 'background:#e9ecef; color:#000;';
            const borderStyle = isProductLink ? 'border: 1px dashed rgba(255,255,255,0.5); background:#1e7e34; color:#fff;' : '';
            const whoName = isUser ? 'You' : 'CSR Representative';
            const timeStr = m.created_at.substring(11, 16);
            
            html += `<div class="bubble ${m.sender}" style="margin-bottom:12px; padding:10px; border-radius:12px; max-width:75%; ${alignment} ${borderStyle}">
              <div class="who" style="font-size:11px; font-weight:bold; opacity:0.8;">${whoName}</div>
              <div class="msg-text" style="margin-top:4px; white-space: pre-wrap;">${escHtml(displayMsg)}</div>
              <div class="time" style="font-size:9px; text-align:right; opacity:0.6; margin-top:4px;">${timeStr}</div>
            </div>`;
          });
          
          chatDiv.innerHTML = html;
          currentMsgCount = msgs.length;
          chatDiv.scrollTop = chatDiv.scrollHeight;
        } else {
          // Show typing indicator briefly for 6s after user sends, then hide if no staff response
          if (msgs.length > 0 && msgs[msgs.length - 1].sender === 'user') {
            const lastTime = new Date(msgs[msgs.length - 1].created_at).getTime();
            const nowTime = new Date().getTime();
            if (isNaN(lastTime) || (nowTime - lastTime < 8000)) {
              showTypingIndicator();
            } else {
              removeTypingIndicator();
            }
          } else {
            removeTypingIndicator();
          }
        }
      }
    })
    .catch(err => console.error('Polling support messages failed:', err));
  }

  // Start polling
  const poller = setInterval(pollMessages, 3000);

  // Form submit handler
  if (chatForm && msgInput) {
    chatForm.addEventListener('submit', (e) => {
      e.preventDefault();
      
      const messageText = msgInput.value.trim();
      if (!messageText) return;
      
      const attachedVal = attachedInput ? attachedInput.value : '';
      
      // Optimistic message append
      const timeNow = new Date().toTimeString().substring(0, 5);
      const isProductLink = attachedVal !== '';
      let displayMsg = isProductLink ? `📦 *Product Inquiry:* ${attachedVal}\n${messageText}` : messageText;
      
      const optBubble = document.createElement('div');
      optBubble.className = 'bubble user';
      optBubble.style.cssText = `margin-bottom:12px; padding:10px; border-radius:12px; max-width:75%; margin-left:auto; background:#007bff; color:#fff; opacity:0.65; position:relative; ${isProductLink ? 'border: 1px dashed rgba(255,255,255,0.5); background:#1e7e34;' : ''}`;
      optBubble.innerHTML = `<div class="who" style="font-size:11px; font-weight:bold; opacity:0.8;">You <span style="font-size:10px; font-weight:normal;">⏳ sending...</span></div>
        <div class="msg-text" style="margin-top:4px; white-space: pre-wrap;">${escHtml(displayMsg)}</div>
        <div class="time" style="font-size:9px; text-align:right; opacity:0.6; margin-top:4px;">${timeNow}</div>`;
      
      // Remove attached card if present
      const attachedCard = document.querySelector('.product-inquiry-card');
      if (attachedCard) attachedCard.remove();
      
      chatDiv.appendChild(optBubble);
      chatDiv.scrollTop = chatDiv.scrollHeight;
      
      msgInput.value = '';
      
      // Send post
      const formData = new FormData();
      formData.append('message', messageText);
      if (attachedVal) {
        formData.append('attached_package', attachedVal);
      }
      
      fetch('support.php?action=send_message', {
        method: 'POST',
        body: formData
      })
      .then(res => res.json())
      .then(data => {
        if (data.success) {
          // Success: poll immediately
          pollMessages();
        } else {
          optBubble.style.background = '#dc3545';
          optBubble.querySelector('.who span').textContent = '⚠️ failed';
          toast(data.error || 'Failed to send message.');
        }
      })
      .catch(err => {
        console.error(err);
        optBubble.style.background = '#dc3545';
        optBubble.querySelector('.who span').textContent = '⚠️ failed';
        toast('Connection failed.');
      });
    });
  }

  function escHtml(str) {
    return str.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
  }
});
