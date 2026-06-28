{{--
    ZenComposer WYSIWYG editor partial.
    Drop @include('components.admin.zencomposer', ['targetId' => 'body']) anywhere in an admin form
    that has a <textarea id="{{ $targetId }}"> to replace it with the rich editor.

    The original textarea is hidden and synced with editor HTML on form submit,
    so Laravel validation and old() work without changes.
--}}
@push('head')
<link rel="stylesheet" href="{{ asset('vendor/zen-composer.min.css') }}">
<style>
    /* Fluid editor shell: fills the viewport between sensible min/max bounds —
       no hardcoded pixel height. The editor itself is told height: 100%. */
    .zencomposer-shell {
        display: flex;
        flex-direction: column;
        min-height: 22rem;
        height: clamp(22rem, 60vh, 48rem);
    }
    .zencomposer-shell .toastui-editor-defaultUI {
        flex: 1;
        min-height: 0;
        font-family: 'Montserrat', sans-serif;
    }
    .toastui-editor-toolbar { border-bottom: 1px solid #e5e7eb; }
</style>
@endpush

@push('scripts')
<script src="{{ asset('vendor/zen-composer.min.js') }}"></script>
<script>
    // Toast UI v3 exposes toastui.Editor; fablepress-editor.js expects zenComposer
    window.zenComposer = window.toastui;
</script>
<script src="{{ asset('vendor/fablepress-editor.js') }}"></script>
<script>
(function () {
    var targetId = {{ Js::from($targetId) }};

    document.addEventListener('DOMContentLoaded', function () {
        var textarea = document.getElementById(targetId);
        if (!textarea) { return; }

        // Build a sibling container for the editor
        // x-ignore keeps Alpine's mutation observer out of the editor subtree
        var container = document.createElement('div');
        container.id = targetId + '-zencomposer';
        container.className = 'zencomposer-shell';
        container.setAttribute('x-ignore', '');
        @isset($height)
            container.style.height = {{ Js::from($height) }};
        @endisset
        textarea.parentNode.insertBefore(container, textarea);
        textarea.style.display = 'none';

        var initialValue = textarea.value || '';

        var editor = window.FableEditor.init({
            el: container,
            initialValue: '',          // start blank, then inject HTML below
            height: '100%',            // fill the fluid .zencomposer-shell
        });

        // Inject existing HTML content into WYSIWYG mode
        if (initialValue.trim() !== '') {
            if (initialValue.trim().startsWith('<')) {
                editor.setHTML(initialValue);
            } else {
                editor.setMarkdown(initialValue);
            }
        }

        // On submit, flush editor HTML back to the hidden textarea
        var form = textarea.closest('form');
        if (form) {
            form.addEventListener('submit', function () {
                textarea.value = editor.getHTML();
            });
        }
    });
})();
</script>
@endpush
