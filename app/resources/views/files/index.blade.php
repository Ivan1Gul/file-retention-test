@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-12 col-xl-11">
                <div class="card app-card mb-4">
                    <div class="card-body p-4 p-lg-5">
                        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-4">
                            <div>
                                <span class="status-pill mb-3">24-hour retention with RabbitMQ notifications</span>
                                <h1 class="h2 mb-2">PDF / DOCX storage</h1>
                                <p class="text-secondary mb-0">
                                    Upload files asynchronously, manage them from one screen, and publish a RabbitMQ notification on every manual or automatic deletion.
                                </p>
                            </div>
                            <div class="text-lg-end">
                                <div class="fw-semibold">Notification recipient</div>
                                <div class="text-secondary">{{ config('rabbitmq.notification_email') }}</div>
                            </div>
                        </div>

                        <div id="alert-container"></div>

                        <form id="upload-form" action="{{ route('files.store') }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            <div class="upload-dropzone p-4 p-lg-5">
                                <div class="row align-items-center gy-3">
                                    <div class="col-lg-8">
                                        <h2 class="h5 mb-2">Upload a new document</h2>
                                        <p class="text-secondary mb-0">
                                            Accepted formats: PDF, DOCX. Maximum size: 10 MB. Files are kept for 24 hours from upload time.
                                        </p>
                                    </div>
                                    <div class="col-lg-4 text-lg-end">
                                        <input class="form-control" type="file" name="file" id="file-input" accept=".pdf,.docx">
                                    </div>
                                </div>
                                <div class="d-flex flex-column flex-lg-row align-items-lg-center gap-3 mt-4">
                                    <button type="submit" class="btn btn-primary px-4" id="upload-button">Upload file</button>
                                    <div class="flex-grow-1">
                                        <div class="progress d-none" id="upload-progress-wrapper" role="progressbar" aria-label="Upload progress" aria-valuemin="0" aria-valuemax="100">
                                            <div id="upload-progress" class="progress-bar progress-bar-striped progress-bar-animated" style="width: 0%">0%</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="card app-card">
                    <div class="card-body p-4 p-lg-5">
                        <div class="d-flex justify-content-between align-items-center gap-3 mb-3">
                            <div>
                                <h2 class="h4 mb-1">Uploaded files</h2>
                                <p class="text-secondary mb-0">Manual deletion also publishes a RabbitMQ message and writes a readable log entry.</p>
                            </div>
                            <span class="badge text-bg-light border" id="files-count">{{ $files->count() }} item(s)</span>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>File</th>
                                        <th>MIME type</th>
                                        <th>Size</th>
                                        <th>Uploaded at</th>
                                        <th>Expires</th>
                                        <th class="text-end">Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="files-table-body">
                                    @forelse ($files as $file)
                                        @include('files.partials.row', ['file' => $file])
                                    @empty
                                        <tr id="empty-state-row">
                                            <td colspan="6" class="text-center text-secondary py-5">
                                                No files uploaded yet.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $(function () {
            const maxFileSizeBytes = 10 * 1024 * 1024;
            const alertContainer = $('#alert-container');
            const progressWrapper = $('#upload-progress-wrapper');
            const progressBar = $('#upload-progress');
            const filesTableBody = $('#files-table-body');
            const filesCount = $('#files-count');
            const fileInput = $('#file-input');

            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                    'Accept': 'application/json'
                }
            });

            const updateCounter = () => {
                const rows = filesTableBody.find('tr[data-file-id]').length;
                filesCount.text(`${rows} item(s)`);

                if (rows === 0 && !$('#empty-state-row').length) {
                    filesTableBody.append(`
                        <tr id="empty-state-row">
                            <td colspan="6" class="text-center text-secondary py-5">No files uploaded yet.</td>
                        </tr>
                    `);
                }

                if (rows > 0) {
                    $('#empty-state-row').remove();
                }
            };

            const buildRow = (file) => `
                <tr data-file-id="${file.id}">
                    <td>
                        <div class="fw-semibold">${file.original_name}</div>
                        <div class="small text-secondary">${file.stored_name}</div>
                    </td>
                    <td>${file.mime_type}</td>
                    <td>${file.size}</td>
                    <td>${file.uploaded_at}</td>
                    <td>
                        <div>${file.expires_at}</div>
                        <div class="small text-secondary">${file.expires_in_human}</div>
                    </td>
                    <td class="text-end">
                        <div class="d-inline-flex gap-2">
                            <a href="${file.download_url}" class="btn btn-outline-secondary btn-sm">Download</a>
                            <button
                                type="button"
                                class="btn btn-outline-danger btn-sm delete-file-button"
                                data-delete-url="${file.delete_url}"
                            >
                                Delete
                            </button>
                        </div>
                    </td>
                </tr>
            `;

            const showAlert = (message, level = 'success') => {
                alertContainer.html(`
                    <div class="alert alert-${level} alert-dismissible fade show" role="alert">
                        ${message}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                `);
            };

            $('#upload-form').on('submit', function (event) {
                event.preventDefault();

                const selectedFile = fileInput[0]?.files?.[0];

                if (!selectedFile) {
                    showAlert('Choose a PDF or DOCX file before uploading.', 'danger');

                    return;
                }

                if (selectedFile.size > maxFileSizeBytes) {
                    showAlert('The file is larger than 10 MB. Please choose a smaller PDF or DOCX file.', 'danger');

                    return;
                }

                const formData = new FormData(this);
                $('#upload-button').prop('disabled', true).text('Uploading...');
                progressWrapper.removeClass('d-none');
                progressBar.css('width', '0%').text('0%');

                $.ajax({
                    url: $(this).attr('action'),
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    xhr: function () {
                        const xhr = $.ajaxSettings.xhr();

                        if (xhr.upload) {
                            xhr.upload.addEventListener('progress', function (event) {
                                if (!event.lengthComputable) {
                                    return;
                                }

                                const percent = Math.round((event.loaded / event.total) * 100);
                                progressBar.css('width', `${percent}%`).text(`${percent}%`);
                            });
                        }

                        return xhr;
                    }
                }).done(function (response) {
                    if (!response?.file?.id) {
                        showAlert(response?.message || 'Unexpected server response. The file was not added.', 'danger');

                        return;
                    }

                    filesTableBody.prepend(buildRow(response.file));
                    $('#upload-form')[0].reset();
                    updateCounter();
                    showAlert(response.message);
                }).fail(function (xhr) {
                    const errors = xhr.responseJSON?.errors ?? {};
                    const message = xhr.status === 413
                        ? 'The uploaded file is too large for the server request. Keep it within 10 MB.'
                        : Object.values(errors).flat().join('<br>') || xhr.responseJSON?.message || 'Upload failed.';
                    showAlert(message, 'danger');
                }).always(function () {
                    $('#upload-button').prop('disabled', false).text('Upload file');
                    progressBar.css('width', '0%').text('0%');
                    progressWrapper.addClass('d-none');
                });
            });

            filesTableBody.on('click', '.delete-file-button', function () {
                const button = $(this);
                const row = button.closest('tr');
                const deleteUrl = button.data('delete-url');

                if (!window.confirm('Delete this file? This also publishes a RabbitMQ message.')) {
                    return;
                }

                button.prop('disabled', true).text('Deleting...');

                $.ajax({
                    url: deleteUrl,
                    type: 'DELETE'
                }).done(function (response) {
                    row.remove();
                    updateCounter();
                    showAlert(response.message, response.message.includes('failed') ? 'warning' : 'success');
                }).fail(function (xhr) {
                    showAlert(xhr.responseJSON?.message || 'Deletion failed.', 'danger');
                    button.prop('disabled', false).text('Delete');
                });
            });
        });
    </script>
@endpush
