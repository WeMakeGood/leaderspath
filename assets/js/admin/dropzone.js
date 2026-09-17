/**
 * Shared drag-and-drop file dropzone for admin metaboxes.
 *
 * Backs Context_Uploader (writes into an existing post's editor) and
 * Cohort_Context (stages a new post's fields ahead of a form submit) — two
 * different targets for the same drag/drop-a-text-file interaction, so the
 * wiring lives here once instead of being hand-copied per metabox.
 *
 * Not used by MD_Drop, which hooks TinyMCE/EditorUploader directly rather
 * than rendering its own dropzone element — a structurally different
 * mechanism, not an oversight.
 */
window.LeadersPathDropzone = (function () {
	'use strict';

	var MAX_FILE_SIZE = 1024 * 1024; // 1 MB — matches MD_Drop's own limit.

	/**
	 * Wire drag/drop + file-select handling onto a dropzone element.
	 *
	 * @param {Object}   config
	 * @param {Element}  config.dropzone   The dropzone container element.
	 * @param {Element}  config.fileInput  The associated <input type="file">.
	 * @param {Function} config.onFileRead Called with (content, file) once a
	 *                                     valid file has been read as text.
	 * @param {string}   [config.tooLargeMessage]
	 * @param {string}   [config.readErrorMessage]
	 */
	function init(config) {
		var dropzone = config.dropzone;
		var fileInput = config.fileInput;

		if (!dropzone || !fileInput || typeof config.onFileRead !== 'function') {
			return;
		}

		function showStatus(message, isError) {
			var existing = dropzone.querySelector('.leaderspath-dropzone__status');
			if (existing) {
				existing.remove();
			}

			var status = document.createElement('p');
			status.className = 'leaderspath-dropzone__status';
			status.className += isError
				? ' leaderspath-dropzone__status--error'
				: ' leaderspath-dropzone__status--success';
			status.textContent = message;
			dropzone.appendChild(status);
		}

		function processFile(file) {
			if (file.size > MAX_FILE_SIZE) {
				showStatus(config.tooLargeMessage || 'File too large. Maximum size is 1 MB.', true);
				return;
			}

			var reader = new FileReader();
			reader.onload = function (e) {
				config.onFileRead(e.target.result, file, showStatus);
			};
			reader.onerror = function () {
				showStatus(config.readErrorMessage || 'Error reading file.', true);
			};
			reader.readAsText(file);
		}

		fileInput.addEventListener('change', function (e) {
			if (e.target.files && e.target.files[0]) {
				processFile(e.target.files[0]);
				e.target.value = '';
			}
		});

		dropzone.addEventListener('dragover', function (e) {
			e.preventDefault();
			e.stopPropagation();
			dropzone.classList.add('leaderspath-dropzone--active');
		});

		dropzone.addEventListener('dragleave', function (e) {
			e.preventDefault();
			e.stopPropagation();
			dropzone.classList.remove('leaderspath-dropzone--active');
		});

		dropzone.addEventListener('drop', function (e) {
			e.preventDefault();
			e.stopPropagation();
			dropzone.classList.remove('leaderspath-dropzone--active');

			if (e.dataTransfer.files && e.dataTransfer.files[0]) {
				processFile(e.dataTransfer.files[0]);
			}
		});
	}

	return {
		init: init,
		MAX_FILE_SIZE: MAX_FILE_SIZE,
	};
})();
