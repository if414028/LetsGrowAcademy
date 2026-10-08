import assert from 'node:assert/strict';
import { test } from 'node:test';
import fileUpload from '../../resources/js/file-upload.js';

// Model only the browser's file-input boundary; exercise the production controller.
function mount({ accept = '.png,.pdf', maxSize = 5 * 1024 * 1024, serverError = '' } = {}) {
    const upload = fileUpload({ maxSize, serverError });
    const form = new EventTarget();
    const input = new EventTarget();
    Object.assign(input, { accept, disabled: false, form, files: [], value: '' });
    upload.$refs = { input };
    input.addEventListener('change', () => upload.choose({ target: input }));
    upload.init();
    return { upload, input, form };
}

function choose(input, file) {
    input.files = file ? [file] : [];
    input.value = file ? `C:\\fakepath\\${file.name}` : '';
    input.dispatchEvent(new Event('change', { bubbles: true }));
}

test('choosing an image exposes its name, size and local preview; clearing releases it', () => {
    const { upload, input } = mount();
    choose(input, new File(['image'], 'photo.png', { type: 'image/png' }));
    assert.equal(upload.fileName, 'photo.png');
    assert.equal(upload.fileSize, '1 KB');
    assert.match(upload.previewUrl, /^blob:/);
    assert.equal(upload.error, '');
    upload.clear();
    assert.equal(input.value, '');
    assert.equal(upload.fileName, '');
    assert.equal(upload.previewUrl, '');
    upload.destroy();
});

test('PDF and uppercase extensions work without an image preview', () => {
    const { upload, input } = mount();
    choose(input, new File(['document'], 'KTP.PDF', { type: 'application/pdf' }));
    assert.equal(upload.fileName, 'KTP.PDF');
    assert.equal(upload.previewUrl, '');
    assert.equal(upload.error, '');
    upload.destroy();
});

test('the exact size limit is accepted and oversized files are removed from the input', () => {
    const { upload, input } = mount({ maxSize: 1024 * 1024 });
    choose(input, new File([new Uint8Array(1024 * 1024)], 'limit.pdf'));
    assert.equal(upload.fileName, 'limit.pdf');
    choose(input, new File([new Uint8Array(1024 * 1024 + 1)], 'too-large.pdf'));
    assert.equal(input.value, '');
    assert.equal(upload.fileName, '');
    assert.match(upload.error, /Maksimal 1 MB/);
    choose(input, new File(['document'], 'valid.pdf'));
    assert.equal(upload.error, '');
    assert.equal(upload.fileName, 'valid.pdf');
    upload.destroy();
});

test('unsupported files get an inline error and a valid replacement clears it', () => {
    const { upload, input } = mount({ accept: 'image/*' });
    choose(input, new File(['text'], 'notes.txt', { type: 'text/plain' }));
    assert.match(upload.error, /Format file tidak didukung/);
    assert.equal(input.value, '');
    choose(input, new File(['image'], 'photo.webp', { type: 'image/webp' }));
    assert.equal(upload.error, '');
    assert.equal(upload.fileName, 'photo.webp');
    upload.destroy();
});

test('dropping a file populates the native input and dispatches its change event', () => {
    const previousTransfer = globalThis.DataTransfer;
    globalThis.DataTransfer = class {
        files = [];
        items = { add: (file) => this.files.push(file) };
    };
    try {
        const { upload, input } = mount();
        const file = new File(['document'], 'dropped.pdf', { type: 'application/pdf' });
        let changes = 0;
        input.addEventListener('change', () => { changes += 1; });
        upload.drop({ dataTransfer: { files: [file] } });
        assert.equal(input.files[0], file);
        assert.equal(upload.fileName, file.name);
        assert.equal(changes, 1);
        upload.drop({ dataTransfer: { files: [file, file] } });
        assert.match(upload.error, /satu file/);
        assert.equal(input.files[0], file);
        upload.drop({ dataTransfer: { files: [] } });
        assert.match(upload.error, /bukan folder/);
        input.disabled = true;
        upload.drop({ dataTransfer: { files: [new File(['other'], 'other.pdf')] } });
        assert.equal(input.files[0], file);
        upload.destroy();
    } finally {
        globalThis.DataTransfer = previousTransfer;
    }
});

test('nested drag events keep feedback active until the file leaves the zone', () => {
    const { upload } = mount();
    const event = { dataTransfer: { types: ['Files'] } };
    upload.dragEnter(event);
    upload.dragEnter(event);
    upload.dragLeave();
    assert.equal(upload.dragging, true);
    upload.dragLeave();
    assert.equal(upload.dragging, false);
    upload.dragEnter({ dataTransfer: { types: ['text/plain'] } });
    assert.equal(upload.dragging, false);
    upload.destroy();
});

test('form reset clears the selected file and server validation feedback', async () => {
    const { upload, input, form } = mount({ serverError: 'Server validation error' });
    assert.equal(upload.error, 'Server validation error');
    choose(input, new File(['document'], 'reset.pdf'));
    form.dispatchEvent(new Event('reset'));
    await new Promise((resolve) => queueMicrotask(resolve));
    assert.equal(upload.fileName, '');
    assert.equal(upload.error, '');
    assert.equal(input.value, '');
    upload.destroy();
});
