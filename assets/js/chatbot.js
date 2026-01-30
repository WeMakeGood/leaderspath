/**
 * LeadersPath Chatbot Frontend JavaScript.
 *
 * Handles the interactive chat functionality with the Claude API.
 * Uses TinyMCE for rich text input with no visible toolbar.
 * Keyboard shortcuts: Ctrl/Cmd+B (bold), Ctrl/Cmd+I (italic), etc.
 *
 * @package LeadersPath
 * @since 0.1.0
 */

(function() {
  'use strict';

  // Configuration from PHP via wp_localize_script.
  const config = window.leaderspathChatbot || {};

  // State.
  let history = [];
  let isLoading = false;
  let editor = null;

  /**
   * Initialize the chatbot when DOM is ready.
   */
  function init() {
    const forms = document.querySelectorAll('.leaderspath-chatbot__form');

    if (!forms.length) {
      return;
    }

    forms.forEach(form => {
      form.addEventListener('submit', handleSubmit);
    });

    // Initialize TinyMCE on the input element.
    initTinyMCE();
  }

  /**
   * Initialize TinyMCE on the chat input element.
   *
   * Configured with no toolbar for a clean interface.
   * Users can use keyboard shortcuts for formatting.
   */
  function initTinyMCE() {
    if (!config.inputId || typeof tinymce === 'undefined') {
      return;
    }

    tinymce.init({
      selector: '#' + config.inputId,
      inline: true,
      toolbar: false,
      menubar: false,
      plugins: '', // No plugins - use core TinyMCE only for keyboard shortcuts.
      skin: false, // Disable skin CSS to prevent interference with theme styles.
      content_css: false, // Disable content CSS loading.
      setup: function(ed) {
        editor = ed;

        // Handle Enter to submit (Shift+Enter for new line).
        ed.on('keydown', function(e) {
          if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            const form = document.querySelector('.leaderspath-chatbot__form');
            if (form) {
              form.dispatchEvent(new Event('submit', { cancelable: true }));
            }
          }
        });
      },
      init_instance_callback: function(ed) {
        // Show placeholder when empty.
        updatePlaceholder(ed);
        ed.on('input', function() {
          updatePlaceholder(ed);
        });
        ed.on('focus', function() {
          updatePlaceholder(ed);
        });
        ed.on('blur', function() {
          updatePlaceholder(ed);
        });
      }
    });
  }

  /**
   * Update placeholder visibility based on editor content.
   *
   * @param {tinymce.Editor} ed TinyMCE editor instance.
   */
  function updatePlaceholder(ed) {
    const content = ed.getContent({ format: 'text' }).trim();
    const container = ed.getContainer();
    if (container) {
      if (content === '') {
        container.classList.add('mce-placeholder');
      } else {
        container.classList.remove('mce-placeholder');
      }
    }
  }

  /**
   * Get content from TinyMCE and convert to Markdown.
   *
   * @returns {string} Markdown-formatted message.
   */
  function getEditorContent() {
    if (!editor) {
      return '';
    }

    const html = editor.getContent();
    return htmlToMarkdown(html);
  }

  /**
   * Clear the TinyMCE editor content.
   */
  function clearEditor() {
    if (editor) {
      editor.setContent('');
      updatePlaceholder(editor);
    }
  }

  /**
   * Focus the TinyMCE editor.
   */
  function focusEditor() {
    if (editor) {
      editor.focus();
    }
  }

  /**
   * Convert HTML to Markdown.
   *
   * Handles common formatting from TinyMCE:
   * - <strong>/<b> → **text**
   * - <em>/<i> → _text_
   * - <code> → `text`
   * - <pre><code> → ```text```
   * - <a href> → [text](url)
   * - <p> → paragraphs with double newline
   * - <br> → single newline
   * - <ul>/<ol>/<li> → list items
   *
   * @param {string} html HTML content from TinyMCE.
   * @returns {string} Markdown-formatted text.
   */
  function htmlToMarkdown(html) {
    if (!html) {
      return '';
    }

    let md = html;

    // Code blocks (pre > code) - must be before inline code.
    md = md.replace(/<pre[^>]*><code[^>]*>([\s\S]*?)<\/code><\/pre>/gi, '\n```\n$1\n```\n');

    // Inline code.
    md = md.replace(/<code[^>]*>(.*?)<\/code>/gi, '`$1`');

    // Bold.
    md = md.replace(/<(strong|b)[^>]*>(.*?)<\/\1>/gi, '**$2**');

    // Italic.
    md = md.replace(/<(em|i)[^>]*>(.*?)<\/\1>/gi, '_$2_');

    // Links.
    md = md.replace(/<a[^>]+href=["']([^"']+)["'][^>]*>(.*?)<\/a>/gi, '[$2]($1)');

    // List items.
    md = md.replace(/<li[^>]*>(.*?)<\/li>/gi, '- $1\n');
    md = md.replace(/<\/?[uo]l[^>]*>/gi, '\n');

    // Paragraphs - convert to double newlines.
    md = md.replace(/<p[^>]*>(.*?)<\/p>/gi, '$1\n\n');

    // Line breaks.
    md = md.replace(/<br\s*\/?>/gi, '\n');

    // Remove remaining HTML tags.
    md = md.replace(/<[^>]+>/g, '');

    // Decode HTML entities.
    md = decodeHtmlEntities(md);

    // Clean up whitespace.
    md = md.replace(/\n{3,}/g, '\n\n'); // Max 2 consecutive newlines.
    md = md.trim();

    return md;
  }

  /**
   * Decode HTML entities.
   *
   * @param {string} text Text with HTML entities.
   * @returns {string} Decoded text.
   */
  function decodeHtmlEntities(text) {
    const textarea = document.createElement('textarea');
    textarea.innerHTML = text;
    return textarea.value;
  }

  /**
   * Handle form submission.
   *
   * @param {Event} e Submit event.
   */
  async function handleSubmit(e) {
    e.preventDefault();

    if (isLoading) {
      return;
    }

    const form = e.target;
    const container = form.closest('.leaderspath-chatbot__chat');
    const messagesEl = container.querySelector('.leaderspath-chatbot__messages');
    const sendBtn = form.querySelector('.leaderspath-chatbot__send');

    // Get message from TinyMCE and convert to Markdown.
    const message = getEditorContent();

    if (!message) {
      return;
    }

    // Clear editor and disable UI.
    clearEditor();
    setLoading(true, sendBtn);

    // Add user message to UI.
    appendMessage(messagesEl, 'user', message);

    // Add to history for API.
    history.push({ role: 'user', content: message });

    // Show loading indicator.
    const loadingEl = appendLoading(messagesEl);

    try {
      const response = await sendMessage(message);

      // Remove loading indicator.
      loadingEl.remove();

      // Add assistant response to UI.
      appendMessage(messagesEl, 'assistant', response.content);

      // Add to history.
      history.push({ role: 'assistant', content: response.content });

    } catch (error) {
      // Remove loading indicator.
      loadingEl.remove();

      // Show error message.
      appendError(messagesEl, error.message || config.errorMsg || 'Something went wrong.');

      // Remove the failed user message from history.
      history.pop();
    } finally {
      setLoading(false, sendBtn);
      focusEditor();
    }
  }

  /**
   * Send a message to the API.
   *
   * @param {string} message User message.
   * @returns {Promise<Object>} API response.
   */
  async function sendMessage(message) {
    const response = await fetch(config.apiUrl, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-WP-Nonce': config.nonce,
      },
      body: JSON.stringify({
        lesson_id: config.lessonId,
        message: message,
        history: history.slice(0, -1), // Exclude the message we just added.
        model: config.model,
      }),
    });

    const data = await response.json();

    if (!response.ok) {
      throw new Error(data.message || 'Request failed');
    }

    return data;
  }

  /**
   * Append a message bubble to the messages container.
   *
   * @param {Element} container Messages container element.
   * @param {string} role 'user' or 'assistant'.
   * @param {string} content Message content (Markdown).
   */
  function appendMessage(container, role, content) {
    const bubble = document.createElement('div');
    bubble.className = `leaderspath-chatbot__message leaderspath-chatbot__message--${role}`;
    bubble.innerHTML = formatContent(content);
    container.appendChild(bubble);
    scrollToMessage(bubble);
  }

  /**
   * Append a loading indicator.
   *
   * @param {Element} container Messages container element.
   * @returns {Element} The loading element (for removal later).
   */
  function appendLoading(container) {
    const loading = document.createElement('div');
    loading.className = 'leaderspath-chatbot__message leaderspath-chatbot__message--assistant leaderspath-chatbot__message--loading';
    loading.innerHTML = `
      <div class="leaderspath-chatbot__loading">
        <span class="leaderspath-chatbot__loading-dot"></span>
        <span class="leaderspath-chatbot__loading-dot"></span>
        <span class="leaderspath-chatbot__loading-dot"></span>
      </div>
    `;
    loading.setAttribute('aria-label', config.loadingMsg || 'Thinking...');
    container.appendChild(loading);
    scrollToMessage(loading);
    return loading;
  }

  /**
   * Append an error message.
   *
   * @param {Element} container Messages container element.
   * @param {string} message Error message.
   */
  function appendError(container, message) {
    const error = document.createElement('div');
    error.className = 'leaderspath-chatbot__message leaderspath-chatbot__message--error';
    error.textContent = message;
    error.setAttribute('role', 'alert');
    container.appendChild(error);
    scrollToMessage(error);
  }

  /**
   * Format message content for display using marked.js.
   *
   * @param {string} content Raw Markdown content.
   * @returns {string} Formatted HTML.
   */
  function formatContent(content) {
    if (!content) {
      return '';
    }

    // Use marked.js for Markdown parsing.
    if (typeof marked !== 'undefined') {
      // Normalize line endings and ensure proper spacing for block elements.
      let normalized = content
        .replace(/\r\n/g, '\n')  // Windows line endings
        .replace(/\r/g, '\n');   // Old Mac line endings

      return marked.parse(normalized, {
        gfm: true,       // GitHub Flavored Markdown
        breaks: true     // Convert single line breaks to <br>
      });
    }

    // Fallback: return escaped content if marked isn't available.
    const div = document.createElement('div');
    div.textContent = content;
    return div.innerHTML;
  }

  /**
   * Scroll a message into view at the top of the chat area.
   *
   * @param {Element} message The message element to scroll to.
   */
  function scrollToMessage(message) {
    message.scrollIntoView({ behavior: 'smooth', block: 'start' });
  }

  /**
   * Set loading state.
   *
   * @param {boolean} loading Whether currently loading.
   * @param {Element} sendBtn Send button element.
   */
  function setLoading(loading, sendBtn) {
    isLoading = loading;

    if (sendBtn) {
      sendBtn.disabled = loading;
      sendBtn.setAttribute('aria-busy', loading.toString());
    }

    // Disable/enable the TinyMCE editor.
    if (editor) {
      if (loading) {
        editor.getBody().setAttribute('contenteditable', 'false');
      } else {
        editor.getBody().setAttribute('contenteditable', 'true');
      }
    }
  }

  // Initialize when DOM is ready.
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }

})();
