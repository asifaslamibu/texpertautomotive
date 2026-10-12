@extends('admin.layouts.app')

@section('title', $blog->exists ? 'Edit Post' : 'New Post')
@section('heading', $blog->exists ? 'Edit Post' : 'New Post')

@section('content')

@php $inp = 'w-full rounded-lg border border-white/10 bg-slate-950/50 text-slate-100 placeholder:text-slate-500 px-3 py-2.5 text-sm outline-none focus:border-slate-400 focus:ring-2 focus:ring-slate-500/30'; @endphp

<form method="POST" action="{{ $blog->exists ? route('admin.blogs.update', $blog) : route('admin.blogs.store') }}" enctype="multipart/form-data" class="mx-auto max-w-3xl">
    @csrf
    @if ($blog->exists) @method('PUT') @endif

    <div class="space-y-5 rounded-2xl border border-white/10 bg-slate-900/70 backdrop-blur p-6 shadow-sm">
        <div>
            <label class="mb-1.5 block text-sm font-medium text-slate-200">Title *</label>
            <input name="title" value="{{ old('title', $blog->title) }}" required placeholder="e.g. 5 HVAC maintenance tips for winter" class="{{ $inp }}">
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="mb-1.5 block text-sm font-medium text-slate-200">Author</label>
                <input name="author" value="{{ old('author', $blog->author) }}" placeholder="e.g. Texpert Deals Team" class="{{ $inp }}">
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-slate-200">Publish date</label>
                <input type="datetime-local" name="published_at" value="{{ old('published_at', optional($blog->published_at)->format('Y-m-d\TH:i')) }}" class="{{ $inp }}">
            </div>
        </div>

        <div>
            <label class="mb-1.5 block text-sm font-medium text-slate-200">Excerpt <span class="font-normal text-slate-400">(short summary shown on the blog listing)</span></label>
            <textarea name="excerpt" rows="2" placeholder="One or two sentences…" class="{{ $inp }}">{{ old('excerpt', $blog->excerpt) }}</textarea>
        </div>

        {{-- cover image --}}
        <div data-image-field>
            <label class="mb-1.5 block text-sm font-medium text-slate-200">Cover image</label>
            <div class="flex items-center gap-4">
                <span class="grid h-20 w-32 shrink-0 place-items-center overflow-hidden rounded-xl border border-white/10 bg-slate-950">
                    <img data-image-preview src="{{ $blog->image ? media($blog->image) : '' }}" class="{{ $blog->image ? '' : 'hidden' }} max-h-full max-w-full object-cover" alt="">
                </span>
                <input type="file" name="image_file" accept="image/*" data-image-input
                       class="block w-full text-sm text-slate-400 file:mr-3 file:rounded-lg file:border-0 file:bg-white/10 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-slate-200 hover:file:bg-white/20">
            </div>
            <input name="image" value="{{ old('image', $blog->image) }}" placeholder="…or paste an image URL" class="mt-3 {{ $inp }}">
            <p class="mt-1 text-xs text-slate-400">JPG/PNG/WEBP · max 4MB.</p>
        </div>

        <div>
            <div class="mb-1.5 flex flex-wrap items-center justify-between gap-2">
                <label class="block text-sm font-medium text-slate-200">Body</label>
                {{-- insert an image into the body at the cursor --}}
                <div class="flex items-center gap-2" data-body-image>
                    <input type="file" accept="image/*" data-bimg-input class="hidden">
                    <button type="button" data-bimg-btn class="rounded-lg border border-white/10 bg-white/5 px-3 py-1.5 text-xs font-semibold text-slate-200 hover:bg-white/10">🖼️ Insert image at cursor</button>
                    <span data-bimg-status class="text-xs text-slate-400"></span>
                </div>
            </div>
            <textarea name="body" data-body rows="16" placeholder="Write your article here. Plain text works — line breaks become paragraphs. Basic HTML (&lt;h2&gt;, &lt;p&gt;, &lt;ul&gt;, &lt;a&gt;, &lt;img&gt;, &lt;strong&gt;) is also supported." class="{{ $inp }} font-mono leading-relaxed">{{ old('body', $blog->body) }}</textarea>
            <p class="mt-1 text-xs text-slate-400">Click <b>Insert image at cursor</b>, pick a file — it uploads and drops an <code class="text-slate-300">&lt;img&gt;</code> right where your cursor is.</p>
        </div>

        @push('scripts')
        <script>
        (function () {
            var wrap = document.querySelector('[data-body-image]');
            if (!wrap) return;
            var input = wrap.querySelector('[data-bimg-input]');
            var btn = wrap.querySelector('[data-bimg-btn]');
            var status = wrap.querySelector('[data-bimg-status]');
            var body = document.querySelector('[data-body]');
            var url = @json(route('admin.blogs.upload-image'));
            var token = @json(csrf_token());

            btn.addEventListener('click', function () { input.click(); });
            input.addEventListener('change', async function () {
                var file = input.files[0];
                if (!file) return;
                status.textContent = 'Uploading…';
                var fd = new FormData();
                fd.append('file', file);
                try {
                    var res = await fetch(url, { method: 'POST', headers: { 'X-CSRF-TOKEN': token, 'X-Requested-With': 'XMLHttpRequest' }, body: fd });
                    var data = await res.json();
                    if (!res.ok || !data.url) throw new Error((data.message) || 'Upload failed');
                    var snippet = '\n<figure>\n  <img src="' + data.url + '" alt="" />\n</figure>\n';
                    var start = body.selectionStart || body.value.length;
                    var end = body.selectionEnd || body.value.length;
                    body.value = body.value.slice(0, start) + snippet + body.value.slice(end);
                    body.focus();
                    body.selectionStart = body.selectionEnd = start + snippet.length;
                    status.textContent = '✓ Inserted';
                    setTimeout(function () { status.textContent = ''; }, 2500);
                } catch (e) {
                    status.textContent = e.message || 'Upload failed';
                }
                input.value = '';
            });
        })();
        </script>
        @endpush

        {{-- SEO --}}
        <details class="rounded-lg border border-white/10 bg-slate-950/40 p-4">
            <summary class="cursor-pointer text-sm font-semibold text-slate-200">SEO (optional)</summary>
            <div class="mt-4 space-y-4">
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-slate-200">Meta title</label>
                    <input name="meta_title" value="{{ old('meta_title', $blog->meta_title) }}" placeholder="Defaults to the post title" class="{{ $inp }}">
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-slate-200">Meta description</label>
                    <textarea name="meta_description" rows="2" placeholder="Defaults to the excerpt" class="{{ $inp }}">{{ old('meta_description', $blog->meta_description) }}</textarea>
                </div>
            </div>
        </details>

        <label class="flex items-center gap-2 text-sm font-medium text-slate-200">
            <input type="checkbox" name="published" value="1" @checked(old('published', $blog->published ?? true)) class="rounded border-white/10 text-slate-200"> Published (visible on the blog)
        </label>
    </div>

    <div class="mt-6 flex items-center justify-end gap-3">
        <a href="{{ route('admin.blogs.index') }}" class="rounded-lg border border-white/10 px-5 py-2.5 text-sm font-semibold text-slate-300 hover:bg-white/5">Cancel</a>
        <button class="rounded-lg bg-white px-6 py-2.5 text-sm font-bold text-slate-900 shadow-sm transition hover:bg-slate-200">{{ $blog->exists ? 'Update Post' : 'Create Post' }}</button>
    </div>
</form>

@endsection
