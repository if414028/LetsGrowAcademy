@props([
    'name',
    'id' => null,
    'label' => 'file',
    'accept' => '',
    'maxSize' => 2,
    'hint' => '',
])

@php
    $inputId = $id ?? 'upload-' . str_replace(['[', ']'], ['-', ''], $name);
    $descriptionId = $inputId . '-hint';
    $statusId = $inputId . '-status';
    $errorId = $inputId . '-error';
@endphp

<div class="file-upload" data-file-upload
    x-data="fileUpload({{ Illuminate\Support\Js::from([
        'maxSize' => (int) ($maxSize * 1024 * 1024),
        'serverError' => $errors->first($name),
    ]) }})">
    <div class="file-upload__dropzone"
        :class="{ 'is-dragging': dragging, 'has-file': fileName, 'has-error': error }"
        @dragenter.prevent="dragEnter($event)"
        @dragover.prevent="dragOver($event)"
        @dragleave.prevent="dragLeave()"
        @drop.prevent="drop($event)">
        <input {{ $attributes->merge(['class' => 'file-upload__input']) }}
            id="{{ $inputId }}" type="file" name="{{ $name }}" accept="{{ $accept }}"
            aria-label="Pilih file untuk {{ $label }}"
            aria-describedby="{{ $descriptionId }} {{ $statusId }} {{ $errorId }}"
            :aria-invalid="error ? 'true' : 'false'"
            x-ref="input" @change="choose($event)">

        <span class="file-upload__icon" aria-hidden="true">
            <svg viewBox="0 0 32 32" fill="none">
                <path d="M5 9a3 3 0 0 1 3-3h6l3 3h7a3 3 0 0 1 3 3v2H12a3 3 0 0 0-2.9 2.2L6 26H5V9Z" fill="currentColor"/>
                <path d="M11.4 15H28a1.5 1.5 0 0 1 1.4 2l-3 9a2 2 0 0 1-1.9 1.4H8l3.4-12.4Z" fill="currentColor" opacity=".45"/>
            </svg>
        </span>
        <p class="file-upload__prompt">
            <span x-text="dragging ? 'Lepaskan file di sini' : 'Seret file ke sini atau'">Seret file ke sini atau</span>
            <span class="file-upload__choose" x-show="!dragging">pilih file</span>
        </p>
        <p id="{{ $descriptionId }}" class="file-upload__hint">{{ $hint }}</p>
    </div>

    <div class="file-upload__selection" :class="{ 'has-file': fileName }">
        <img x-show="previewUrl" x-cloak :src="previewUrl || null" alt="Preview file yang dipilih" class="file-upload__preview">
        <svg x-show="!previewUrl" class="file-upload__attachment" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="m21 11-8.5 8.5a6 6 0 0 1-8.5-8.5l9-9a4 4 0 0 1 5.7 5.7l-9 9a2 2 0 0 1-2.8-2.8L15 6"/>
        </svg>
        <p id="{{ $statusId }}" class="file-upload__status" role="status" aria-live="polite" aria-atomic="true">
            <span class="file-upload__name" :title="fileName" x-text="fileName || 'Belum ada file dipilih'">Belum ada file dipilih</span>
            <span x-show="fileName" x-cloak class="file-upload__size" x-text="fileSize ? fileSize + ' · Siap disimpan' : ''"></span>
        </p>
        <button type="button" class="file-upload__remove" x-show="fileName" x-cloak
            @click="clear(); $refs.input.focus()" aria-label="Hapus pilihan file untuk {{ $label }}" title="Hapus pilihan file">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                <path stroke-linecap="round" d="m6 6 12 12M18 6 6 18"/>
            </svg>
        </button>
    </div>
    <p id="{{ $errorId }}" class="file-upload__error" x-show="error" x-cloak x-text="error" role="alert"></p>
</div>
