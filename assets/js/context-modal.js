/**
 * Context Library Modal functionality.
 *
 * Handles the "View Content" modal for displaying context file contents.
 *
 * @package LeadersPath
 * @since 0.1.0
 */

(function() {
  'use strict';

  /**
   * Modal state and elements.
   */
  let modal = null;
  let modalContent = null;
  let modalTitle = null;
  let modalBody = null;
  let modalClose = null;

  /**
   * Create the modal HTML structure.
   *
   * @returns {HTMLElement} The modal element.
   */
  function createModal() {
    const modalElement = document.createElement('div');
    modalElement.className = 'leaderspath-context-modal';
    modalElement.setAttribute('role', 'dialog');
    modalElement.setAttribute('aria-modal', 'true');
    modalElement.setAttribute('aria-hidden', 'true');

    modalElement.innerHTML = `
      <div class="leaderspath-context-modal__backdrop"></div>
      <div class="leaderspath-context-modal__container">
        <div class="leaderspath-context-modal__content">
          <div class="leaderspath-context-modal__header">
            <h3 class="leaderspath-context-modal__title"></h3>
            <button type="button" class="leaderspath-context-modal__close" aria-label="Close modal">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
          <div class="leaderspath-context-modal__body"></div>
        </div>
      </div>
    `;

    document.body.appendChild(modalElement);

    return modalElement;
  }

  /**
   * Initialize modal elements.
   */
  function initModal() {
    if (modal) {
      return;
    }

    modal = createModal();
    modalContent = modal.querySelector('.leaderspath-context-modal__content');
    modalTitle = modal.querySelector('.leaderspath-context-modal__title');
    modalBody = modal.querySelector('.leaderspath-context-modal__body');
    modalClose = modal.querySelector('.leaderspath-context-modal__close');

    // Event listeners.
    modalClose.addEventListener('click', closeModal);
    modal.querySelector('.leaderspath-context-modal__backdrop').addEventListener('click', closeModal);
    document.addEventListener('keydown', handleKeydown);
  }

  /**
   * Handle keyboard events.
   *
   * @param {KeyboardEvent} event Keyboard event.
   */
  function handleKeydown(event) {
    if (event.key === 'Escape' && isModalOpen()) {
      closeModal();
    }
  }

  /**
   * Check if modal is currently open.
   *
   * @returns {boolean} True if modal is open.
   */
  function isModalOpen() {
    return modal && modal.getAttribute('aria-hidden') === 'false';
  }

  /**
   * Open the modal with content.
   *
   * @param {string} title   Modal title.
   * @param {string} content Modal body content (HTML).
   */
  function openModal(title, content) {
    initModal();

    modalTitle.textContent = title;
    modalBody.innerHTML = content;

    modal.setAttribute('aria-hidden', 'false');
    modal.classList.add('leaderspath-context-modal--open');
    document.body.classList.add('leaderspath-modal-open');

    // Focus management.
    modalClose.focus();
  }

  /**
   * Close the modal.
   */
  function closeModal() {
    if (!modal) {
      return;
    }

    modal.setAttribute('aria-hidden', 'true');
    modal.classList.remove('leaderspath-context-modal--open');
    document.body.classList.remove('leaderspath-modal-open');

    // Return focus to the trigger button.
    if (window.leaderspathLastFocusedElement) {
      window.leaderspathLastFocusedElement.focus();
      window.leaderspathLastFocusedElement = null;
    }
  }

  /**
   * Handle view button clicks.
   *
   * @param {Event} event Click event.
   */
  function handleViewClick(event) {
    const button = event.target.closest('.leaderspath-context-library__view-button');
    if (!button) {
      return;
    }

    event.preventDefault();

    const fileId = button.getAttribute('data-file-id');
    if (!fileId) {
      return;
    }

    // Store the clicked button for focus return.
    window.leaderspathLastFocusedElement = button;

    // Find the context file data.
    const contextFiles = window.leaderspathContextFiles || [];
    const file = contextFiles.find(function(f) {
      return String(f.id) === String(fileId);
    });

    if (file) {
      // Convert markdown-like content to HTML paragraphs.
      // For a more robust solution, consider using a markdown parser.
      const htmlContent = formatContent(file.content);
      openModal(file.title, htmlContent);
    } else {
      console.warn('Context file not found:', fileId);
    }
  }

  /**
   * Format content for display.
   *
   * Basic formatting - converts line breaks to paragraphs.
   * For full markdown support, integrate a markdown parser.
   *
   * @param {string} content Raw content.
   * @returns {string} Formatted HTML.
   */
  function formatContent(content) {
    if (!content) {
      return '<p>No content available.</p>';
    }

    // Split by double newlines for paragraphs.
    const paragraphs = content.split(/\n\n+/);

    return paragraphs
      .map(function(para) {
        // Preserve single newlines as <br>.
        const formatted = para.trim().replace(/\n/g, '<br>');
        return formatted ? '<p>' + formatted + '</p>' : '';
      })
      .filter(Boolean)
      .join('');
  }

  /**
   * Initialize event listeners.
   */
  function init() {
    // Use event delegation for view buttons.
    document.addEventListener('click', handleViewClick);
  }

  // Initialize when DOM is ready.
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }

})();
