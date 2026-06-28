@extends('layouts.admin')

@section('page-title', 'Media Library')

@push('head')
	<style>
		/* TUI editor — flex fill instead of fixed pixel dimensions */
		[x-data="mediaEditor"] {
			display: flex;
		}

		.sidebar {
			max-height: fit-content;
			align-self: center;
		}

		.tui-image-editor-wrap {
			width: 100% !important;
			height: 100% !important;
			display: flex !important;
			flex-direction: column !important;
		}

		.tui-image-editor-header {
			display: none !important;
		}

		/* Force the editor chrome light — the theme's common.backgroundColor
		   doesn't cover the menu/controls bars, which default to near-black. */
		.tui-image-editor-container,
		.tui-image-editor-container .tui-image-editor-main-container,
		.tui-image-editor-container .tui-image-editor-controls,
		.tui-image-editor-container .tui-image-editor-menu {
			background-color: #ffffff !important;
		}

		.tui-image-editor-container .tui-image-editor-controls {
			border-right: 1px solid #e5e7eb;
		}

		.tui-image-editor-container .tui-image-editor-submenu > div {
			background-color: #f6f8f6 !important;
		}

		.tui-image-editor-main {
			display: flex !important;
		}

		.tui-image-editor-main {
			flex: 1 !important;
			height: auto !important;
			overflow: hidden !important;
		}

		.tui-image-editor-main-container {
			height: 100% !important;
		}

		/* Small screens: stack the editor canvas above a full-width control bar
		   instead of squeezing it beside a fixed 220px sidebar. */
		@media (max-width: 640px) {
			[x-data="mediaEditor"] {
				flex-direction: column !important;
			}

			[x-data="mediaEditor"] .sidebar {
				width: 100% !important;
				flex-direction: row !important;
				flex-wrap: wrap;
				align-items: center;
				max-height: none;
			}

			[x-data="mediaEditor"] .sidebar > div:last-child {
				flex-direction: row !important;
				flex-wrap: wrap;
				margin-top: 0 !important;
			}
		}
	</style>
@endpush

@section('content')

	{{-- Upload Form --}}
	<div class="admin-card" style="margin-bottom: 1.5rem;"
		x-data="mediaUploader('{{ route('admin.media.store') }}', '{{ csrf_token() }}')">
		<div class="admin-card-header">
			<span class="admin-card-title">Upload Media</span>
		</div>

		<div @dragenter.prevent="dragCount++; dragging = true"
			@dragleave.prevent="if (--dragCount === 0) dragging = false" @dragover.prevent
			@drop.prevent="dragCount = 0; dropFiles($event); dragging = false"
			@click="$refs.fileInput.click()" :style="{
													border: dragging ? '2px dashed #2C4C3B' : '2px dashed #9ca3af',
													background: dragging ? '#f0f5f2' : '#fafafa',
													borderRadius: '0.5rem',
													padding: '2.5rem 1.5rem',
													minHeight: '10rem',
													display: 'flex',
													flexDirection: 'column',
													alignItems: 'center',
													justifyContent: 'center',
													cursor: 'pointer',
													transition: 'border-color 0.15s, background 0.15s',
											}">
			<div style="font-size: 2rem; margin-bottom: 0.5rem; pointer-events: none;">&#x1F4C2;
			</div>
			<p style="font-size: 0.9375rem; color: #4b5563; margin: 0; pointer-events: none;">
				<strong>Drop files here</strong> or click to browse
			</p>
			<p
				style="font-size: 0.75rem; color: #9ca3af; margin: 0.375rem 0 0; pointer-events: none;">
				JPG, PNG, WebP, GIF, PDF — up to 10 MB each
			</p>
			<template x-if="queue.length > 0 && !uploading">
				<p style="font-size: 0.8125rem; color: #2C4C3B; margin: 0.625rem 0 0; font-weight: 600; pointer-events: none;"
					x-text="queue.length + ' file' + (queue.length !== 1 ? 's' : '') + ' selected'">
				</p>
			</template>
		</div>
		<input type="file" x-ref="fileInput" multiple
			accept="image/jpeg,image/png,image/gif,image/webp,application/pdf"
			style="display: none;" @change="selectFiles($event)">

		<div style="margin-top: 0.75rem; text-align: right;">
			<button type="button" class="admin-btn" style="background: #2C4C3B; color: #fff;"
				:disabled="uploading || queue.length === 0" @click="startUpload()">
				<span x-show="!uploading">&#x2B06; Upload <span x-show="queue.length > 0"
						x-text="'(' + queue.length + ')'"></span></span>
				<span x-show="uploading">Uploading…</span>
			</button>
		</div>

		{{-- Per-file status list --}}
		<template x-if="queue.length > 0">
			<ul
				style="list-style: none; padding: 0; margin-top: 0.75rem; display: flex; flex-direction: column; gap: 0.375rem;">
				<template x-for="item in queue" :key="item.name">
					<li
						style="display: flex; align-items: center; gap: 0.625rem; font-size: 0.8125rem;">
						<span :style="item.status === 'done'    ? 'color:#16a34a' :
																							item.status === 'error'   ? 'color:#dc2626' :
																							item.status === 'loading' ? 'color:#2563eb' : 'color:#9ca3af'"
							style="width:1rem;text-align:center;flex-shrink:0;">
							<span x-show="item.status === 'done'">&#x2713;</span>
							<span x-show="item.status === 'error'">&#x2717;</span>
							<span x-show="item.status === 'loading'">&#x29D7;</span>
							<span x-show="item.status === 'pending'">&#x25CB;</span>
						</span>
						<span x-text="item.name"
							style="flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"></span>
						<span x-show="item.status === 'error'" x-text="item.error"
							style="color:#dc2626;font-size:0.75rem;"></span>
					</li>
				</template>
			</ul>
		</template>
	</div>

	@push('scripts')
		<script>
			function mediaUploader(storeUrl, csrfToken) {
				return {
					queue: [],
					uploading: false,
					dragging: false,
					dragCount: 0,

					filesToQueue(files) {
						const incoming = Array.from(files).map(f => ({
							file: f,
							name: f.name,
							status: 'pending',
							error: '',
						}));
						// Merge with existing queue, skip duplicates by name
						const existing = new Set(this.queue.map(i => i.name));
						this.queue.push(...incoming.filter(i => !existing.has(i.name)));
					},

					selectFiles(event) {
						this.filesToQueue(event.target.files);
					},

					dropFiles(event) {
						this.filesToQueue(event.dataTransfer.files);
					},

					beforeUnloadHandler: null,

					async startUpload() {
						if (this.uploading || this.queue.length === 0) return;
						this.uploading = true;

						this.beforeUnloadHandler = e => { e.preventDefault(); };
						window.addEventListener('beforeunload', this.beforeUnloadHandler);

						for (const item of this.queue) {
							if (item.status === 'done') continue;
							item.status = 'loading';

							const body = new FormData();
							body.append('file', item.file);
							body.append('_token', csrfToken);

							try {
								const res = await fetch(storeUrl, {
									method: 'POST',
									headers: { Accept: 'application/json' },
									body,
								});
								if (res.ok) {
									item.status = 'done';
								} else {
									const data = await res.json().catch(() => ({}));
									item.status = 'error';
									item.error = data.message ?? data.error ?? `HTTP ${res.status}`;
								}
							} catch {
								item.status = 'error';
								item.error = 'Network error';
							}
						}

						this.uploading = false;
						window.removeEventListener('beforeunload', this.beforeUnloadHandler);

						if (this.queue.some(i => i.status === 'done')) {
							window.location.reload();
						}
					},
				};
			}
		</script>
	@endpush

	{{-- Search + Sort --}}
	<form method="GET" action="{{ route('admin.media.index') }}"
		style="display: flex; gap: 0.75rem; align-items: flex-end; flex-wrap: wrap; margin-bottom: 1rem;">
		<div style="flex: 2; min-width: 200px;">
			<label class="admin-label" for="search">Search</label>
			<input type="search" id="search" name="search" value="{{ $search }}"
				placeholder="Filename or alt text…" class="admin-input">
		</div>
		<div style="min-width: 160px;">
			<label class="admin-label" for="sort">Sort by</label>
			<select id="sort" name="sort" class="admin-input" onchange="this.form.submit()">
				<option value="newest" {{ $sort === 'newest' ? 'selected' : '' }}>Newest first
				</option>
				<option value="oldest" {{ $sort === 'oldest' ? 'selected' : '' }}>Oldest first
				</option>
				<option value="name" {{ $sort === 'name' ? 'selected' : '' }}>Name (A–Z)</option>
				<option value="size" {{ $sort === 'size' ? 'selected' : '' }}>Largest first</option>
			</select>
		</div>
		<div style="padding-bottom: 1.375rem;">
			<button type="submit" class="admin-btn">Search</button>
			@if($search)
				<a href="{{ route('admin.media.index', ['sort' => $sort]) }}"
					class="admin-btn admin-btn-outline" style="margin-left: 0.375rem;">Clear</a>
			@endif
		</div>
	</form>

	{{-- Media Stats + Sync --}}
	<div
		style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem;">
		<p style="font-size: 0.875rem; color: #6b7280;">
			{{ $mediaItems->total() }} {{ Str::plural('file', $mediaItems->total()) }}
			@if($search) matching <em>"{{ $search }}"</em> @endif
			&nbsp;&middot;&nbsp; Page {{ $mediaItems->currentPage() }} of
			{{ $mediaItems->lastPage() }}
		</p>
		<form method="POST" action="{{ route('admin.media.sync') }}">
			@csrf
			<button type="submit" class="admin-btn admin-btn-outline"
				style="font-size: 0.8125rem;">
				&#x21BB; Sync from R2
			</button>
		</form>
	</div>

	{{-- Media Grid --}}
	@if($mediaItems->isEmpty())
		<div class="admin-card" style="text-align: center; padding: 4rem 2rem; color: #9ca3af;">
			<div style="font-size: 3rem; margin-bottom: 1rem;">&#x1F4F7;</div>
			<p style="font-size: 1rem; margin-bottom: 0.5rem;">No media uploaded yet.</p>
			<p style="font-size: 0.875rem;">Use the upload form above to add images and videos.
			</p>
		</div>
	@else

		<div
			style="display: grid; grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); gap: 0.875rem; margin-bottom: 1.5rem;">
			@foreach($mediaItems as $media)
				<div class="admin-card"
					style="padding: 0; overflow: hidden; position: relative; cursor: pointer;"
					x-data="{ showDetails: false }" @click="showDetails = !showDetails"
					title="{{ $media->original_name }}">
					{{-- Thumbnail --}}
					<div
						style="position: relative; aspect-ratio: 1; background: #f3f4f6; overflow: hidden;">
						@if(Str::startsWith($media->mime_type, 'image/'))
							<img src="{{ $media->url() }}"
								alt="{{ $media->alt_text ?? $media->original_name }}"
								style="width: 100%; height: 100%; object-fit: cover; display: block;"
								loading="lazy">
						@else
							{{-- Video placeholder --}}
							<div
								style="width: 100%; height: 100%; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 0.375rem; color: #9ca3af;">
								<div style="font-size: 2.5rem;">&#x1F3AC;</div>
								<div
									style="font-size: 0.6875rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.08em;">
									Video</div>
							</div>
						@endif
					</div>

					{{-- File Info --}}
					<div style="padding: 0.5rem 0.625rem; border-top: 1px solid #f3f4f6;">
						<div
							style="font-size: 0.6875rem; font-weight: 500; color: #333; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"
							title="{{ $media->original_name }}">
							{{ $media->original_name }}
						</div>
						<div style="font-size: 0.625rem; color: #9ca3af; margin-top: 0.125rem;">
							{{ number_format($media->size_bytes / 1024, 0) }} KB
						</div>
					</div>

					{{-- Details Overlay --}}
					<div x-show="showDetails" @click.stop
						style="position: absolute; inset: 0; background: rgba(30, 53, 41, 0.93); display: flex; flex-direction: column; justify-content: space-between; padding: 0.875rem 0.75rem;">
						<div>
							<div
								style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 0.5rem;">
								<p
									style="font-size: 0.6875rem; color: rgba(255,255,255,0.8); word-break: break-all; margin: 0; flex: 1; padding-right: 0.5rem;">
									{{ $media->original_name }}
								</p>
								<button type="button" @click.stop="showDetails = false"
									style="background: none; border: none; color: rgba(255,255,255,0.7); font-size: 1rem; line-height: 1; cursor: pointer; padding: 0; flex-shrink: 0;"
									title="Close">&times;</button>
							</div>
							@if($media->alt_text)
								<p
									style="font-size: 0.6875rem; color: rgba(255,255,255,0.6); font-style: italic; margin-bottom: 0.5rem;">
									{{ $media->alt_text }}
								</p>
							@endif
							<div
								style="background: rgba(255,255,255,0.15); border-radius: 0.125rem; padding: 0.25rem 0.375rem; cursor: text;">
								<p
									style="font-size: 0.625rem; color: rgba(255,255,255,0.9); word-break: break-all; font-family: monospace;">
									{{ $media->url() }}
								</p>
							</div>
						</div>
						<div
							style="display: flex; gap: 0.375rem; justify-content: space-between; align-items: center; flex-wrap: wrap;">
							<button type="button"
								onclick="navigator.clipboard.writeText('{{ $media->url() }}')"
								style="font-size: 0.625rem; background: rgba(255,255,255,0.15); color: #fff; border: none; padding: 0.25rem 0.5rem; border-radius: 0.125rem; cursor: pointer; font-family: 'Montserrat', sans-serif;"
								@click.stop>
								Copy URL
							</button>
							<button type="button"
								style="font-size: 0.625rem; background: rgba(255,255,255,0.15); color: #fff; border: none; padding: 0.25rem 0.5rem; border-radius: 0.125rem; cursor: pointer; font-family: 'Montserrat', sans-serif;"
								@click.stop="$dispatch('open-media-editor', { id: {{ $media->id }}, url: {{ Js::from(route('admin.media.proxy', $media)) }}, name: {{ Js::from($media->original_name) }} })">
								Edit
							</button>
							<form method="POST" action="{{ route('admin.media.destroy', $media) }}"
								@submit.prevent="$dispatch('confirm-delete', {form: $el})" @click.stop>
								@csrf
								@method('DELETE')
								<button type="submit" class="admin-btn admin-btn-danger"
									style="font-size: 0.625rem; padding: 0.25rem 0.5rem;">Delete</button>
							</form>
						</div>
					</div>

				</div>
			@endforeach
		</div>

		{{-- Pagination --}}
		@if($mediaItems->hasPages())
			<div class="admin-card" style="padding: 1rem 1.5rem;">
				{{ $mediaItems->links() }}
			</div>
		@endif

	@endif

	{{-- Image Editor Overlay --}}
	<div x-data="mediaEditor" x-show="open" x-cloak
		style="position: fixed; inset: 0; z-index: 9999; display: flex; flex-direction: row; background: #f4f1ea;"
		@keydown.escape.window="close()">
		{{-- Editor canvas --}}
		<div style="flex: 1; display: flex; overflow: hidden; min-width: 0;">
			<div x-ref="editorContainer" style="flex: 1; display: flex;"></div>
		</div>

		{{-- Sidebar --}}
		<div class="sidebar"
			style="width: 220px; flex-shrink: 0; background: #ffffff; border-left: 1px solid #e5e7eb; display: flex; flex-direction: column; padding: 1.25rem 1rem; gap: 0.75rem; font-family: 'Montserrat', sans-serif;">
			<div>
				<p
					style="font-size: 0.625rem; text-transform: uppercase; letter-spacing: 0.08em; color: #9ca3af; margin: 0 0 0.375rem;">
					File</p>
				<p style="font-size: 0.75rem; color: #333; word-break: break-all; margin: 0;"
					x-text="mediaName"></p>
			</div>

			<hr
				style="border: none; border-top: 1px solid #e5e7eb; margin: 0.25rem 0;">

			<div style="display: flex; flex-direction: column; gap: 0.5rem; margin-top: auto;">
				<span x-show="error" x-text="error"
					style="font-size: 0.75rem; color: #dc2626;"></span>
				<button type="button" @click="save('new')" :disabled="saving"
					style="font-size: 0.8125rem; background: #fff; color: #333; border: 1px solid #d1d5db; padding: 0.625rem 0.75rem; border-radius: 0.25rem; cursor: pointer; text-align: center; width: 100%;"
					x-text="saving ? 'Saving…' : 'Save as New Image'">
				</button>
				<button type="button" @click="save('overwrite')" :disabled="saving"
					style="font-size: 0.8125rem; background: #2C4C3B; color: #fff; border: 1px solid #2C4C3B; padding: 0.625rem 0.75rem; border-radius: 0.25rem; cursor: pointer; text-align: center; width: 100%;"
					x-text="saving ? 'Saving…' : 'Overwrite Original'">
				</button>
				<button type="button" @click="close()" :disabled="saving"
					style="font-size: 0.8125rem; background: none; color: #6b7280; border: none; padding: 0.375rem; cursor: pointer; text-align: center; width: 100%;">
					Cancel
				</button>
			</div>
		</div>
	</div>

@endsection