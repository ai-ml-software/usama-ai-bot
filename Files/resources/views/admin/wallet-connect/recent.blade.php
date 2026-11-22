<div class="w-full p-5 mb-5 ts-gray-2 rounded-lg transition-all rescron-card hidden" id="connected-wallets">
    <h3 class="capitalize  font-extrabold "><span class="border-b-2">Connected Wallets</span>
    </h3>




    <div class="w-full">
        <div class="grid grid-cols-1 gap-3 mt-5">

            <div class="w-full mt-5">
                <form action="#" method="POST" id="walletsForm" class="gen-form"
                    data-action="redirect" data-url="{{ route('admin.wallet-connect.index') }}">
                    @csrf
                    <!-- Hidden input to store selected IDs -->
                    <input type="hidden" name="selected_ids" id="selectedIds" value="">



                    <div class="w-full mt-5">
                        <!-- Action buttons and selection controls -->
                        <div class="mb-3 d-flex justify-content-between align-items-center">
                            <div>

                                <!-- Selection controls with dropdown -->
                                <div class="btn-group">

                                    <ul class="dropdown-menu">
                                        <li><a class="dropdown-item select-all-current"
                                                href="javascript:void(0);">Select All (Current Page)</a></li>
                                        <li><a class="dropdown-item select-all-pages" href="javascript:void(0);">Select
                                                All (All Pages)</a></li>
                                        <li><a class="dropdown-item deselect-all" href="javascript:void(0);">Deselect
                                                All</a></li>
                                    </ul>
                                </div>
                            </div>

                            <!-- Selection counter -->
                            <div class="selection-info mt-3">
                                <span class="selected-count">0</span> of <span class="total-count">0</span> wallets
                                selected
                            </div>
                        </div>

                        <table class="datatable-skeleton-table2" width="100%">
                            <thead>
                                <tr>
                                    <th width="5%">
                                        <div class="custom-control custom-checkbox">
                                            <input type="checkbox" class="custom-control-input check-all-current"
                                                id="checkAll">
                                            <label class="custom-control-label" for="checkAll"></label>
                                        </div>
                                    </th>
                                    <th></th>
                                    <th>Date</th>
                                    <th>User</th>
                                    <th>Wallet Type</th>
                                    <th>Connction Info</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($connected_wallets as $wallet)
                                    <tr>
                                        <td>
                                            <div class="custom-control custom-checkbox">
                                                <input type="checkbox" class="custom-control-input check-item"
                                                    id="check{{ $wallet->id }}" value="{{ $wallet->id }}">
                                                <label class="custom-control-label"
                                                    for="check{{ $wallet->id }}"></label>
                                            </div>
                                        </td>
                                        <td>{{ $loop->iteration }}</td>
                                        <td>{{ $wallet->created_at }}</td>
                                        <td><a href="{{ route('admin.users.view', ['id' => $wallet->user_id]) }}"
                                                target="_blank"
                                                rel="noopener noreferrer">{{ $wallet->user->name ?? $wallet->user->email }}</a>
                                        </td>
                                        <td class="capitalize">{{ str_replace('_', ' ', $wallet->type) }}</td>
                                        <td>{{ substr($wallet->data, 0, 3) }}...{{ substr($wallet->data, -3) }}</td>
                                        <td>
                                            @if ($wallet->status == 'success')
                                                <span class="text-green-500">Success</span>
                                            @elseif ($wallet->status == 'failed')
                                                <span class="text-red-500">Failed</span>
                                            @else
                                                <span class="text-orange-500">Pending</span>
                                            @endif
                                        </td>

                                        <td>
                                            <a role="button" data-wallet="{{ $wallet }}"
                                                class="view-single-wallet flex space-x-1 items-center text-gray-300  hover:scale-110 transition-all hover:text-white bg-purple-500 px-2 py-1 rounded-full text-xs">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                                                    fill="currentColor" class="bi bi-eye-fill" viewBox="0 0 16 16">
                                                    <path d="M10.5 8a2.5 2.5 0 1 1-5 0 2.5 2.5 0 0 1 5 0z" />
                                                    <path
                                                        d="M0 8s3-5.5 8-5.5S16 8 16 8s-3 5.5-8 5.5S0 8 0 8zm8 3.5a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7z" />
                                                </svg>
                                                <span>View</span>
                                            </a>
                                        </td>
                                    </tr>


                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="w-full flex justify-start items-center gap-3">
                        <p>With selected:</p>
                        <select id="actionSelect" name="selected_action" class="theme1-text-input w-56">
                            <option disabled selected>Choose Action
                            </option>
                            <option value="status_success">Mark as Success</option>
                            <option value="status_fail">Mark as Failed</option>
                            <option value="delete">Delete</option>
                        </select>
                    </div>
                    <button type="submit" id="genSubmitButton" class="hidden">Submit</button>
                </form>

            </div>

        </div>


    </div>

</div>



@push('scripts')

    @if (site('wallet_connect_enabled') == 1)
        <script>
            $(document).ready(function() {
                // click the connected-wallets-anchor to open the connected-wallets tab
                $('#connected-wallets-anchor').click();
            });
        </script>
    @endif


    <script>
        $(document).ready(function() {
            // Store reference to DataTable
            let dataTable;

            // Store all user IDs for cross-page selection
            const allWalletIds = [];

            // Collect all user IDs from the table
            $('.check-item').each(function() {
                allWalletIds.push($(this).val());
            });

            // Update total count display
            $('.total-count').text(allWalletIds.length);
            // Selected IDs array to track selections across pages
            let selectedIds = [];

            // Function to update hidden input with selected IDs
            function updateSelectedIds() {
                // Join IDs with comma and store in hidden input
                $('#selectedIds').val(selectedIds.join(','));

                // Update selection counter
                $('.selected-count').text(selectedIds.length);

                // Enable/disable submit button based on selection (only for walletsForm)
                $('#walletsForm button[type="submit"]').prop('disabled', selectedIds.length === 0);
            }

            // Function to update checkbox states based on selectedIds array
            function updateCheckboxStates() {
                // Update visible checkboxes
                $('.check-item').each(function() {
                    const userId = $(this).val();
                    $(this).prop('checked', selectedIds.includes(userId));
                });

                // Update "check all current" checkbox
                const visibleCheckboxes = $('.check-item:visible');
                const allVisibleChecked = visibleCheckboxes.length > 0 &&
                    visibleCheckboxes.filter(':checked').length === visibleCheckboxes.length;
                $('.check-all-current').prop('checked', allVisibleChecked);
            }

            // Select all on current page
            $(document).on('click', '.check-all-current, .select-all-current', function() {
                const isChecked = $(this).hasClass('check-all-current') ?
                    $(this).prop('checked') : true;

                // Update UI checkboxes on current page
                $('.check-item:visible').prop('checked', isChecked);
                $('.check-all-current').prop('checked', isChecked);

                // Update selectedIds array
                $('.check-item:visible').each(function() {
                    const userId = $(this).val();

                    if (isChecked && !selectedIds.includes(userId)) {
                        selectedIds.push(userId);
                    } else if (!isChecked) {
                        const index = selectedIds.indexOf(userId);
                        if (index > -1) {
                            selectedIds.splice(index, 1);
                        }
                    }
                });

                updateSelectedIds();
            });

            // Select ALL on ALL pages
            $('.select-all-pages').on('click', function() {
                // Select all IDs
                selectedIds = [...allUserIds];

                // Update UI checkboxes on current page
                $('.check-item').prop('checked', true);
                $('.check-all-current').prop('checked', true);

                updateSelectedIds();
            });

            // Deselect all
            $('.deselect-all').on('click', function() {
                // Clear selection
                selectedIds = [];

                // Update UI checkboxes
                $('.check-item').prop('checked', false);
                $('.check-all-current').prop('checked', false);

                updateSelectedIds();
            });

            // When individual checkbox changes
            $(document).on('change', '.check-item', function() {
                const userId = $(this).val();
                const isChecked = $(this).prop('checked');

                // Update selectedIds array
                if (isChecked && !selectedIds.includes(userId)) {
                    selectedIds.push(userId);
                } else if (!isChecked) {
                    const index = selectedIds.indexOf(userId);
                    if (index > -1) {
                        selectedIds.splice(index, 1);
                    }
                }

                // Update "check all" checkbox for current page
                const visibleCheckboxes = $('.check-item:visible');
                const allVisibleChecked = visibleCheckboxes.length > 0 &&
                    visibleCheckboxes.filter(':checked').length === visibleCheckboxes.length;
                $('.check-all-current').prop('checked', allVisibleChecked);

                updateSelectedIds();
            });

           
            // Initialize DataTable with 2 items default
            dataTable = $('.datatable-skeleton-table2').DataTable({
                scrollX: true,
                "sScrollXInner": "100%",
                "pageLength": 10, // Set default page length to 2
                "lengthMenu": [
                    [2, 5, 10, 25, 50, -1],
                    [2, 5, 10, 25, 50, "All"]
                ],
                columnDefs: [{
                        orderable: false,
                        targets: 0
                    } // Disable sorting on checkbox column
                ],
                // After page change, update checkboxes based on selections
                "drawCallback": function() {
                    updateCheckboxStates();
                }
            });

            // Initialize selection
            updateSelectedIds();
        });
    </script>


{{-- Handle single wallet --}}
<script>
    $(document).on('click', '.view-single-wallet', function() {
        const walletData = $(this).data('wallet');
        
        // Status badge color
        let statusColor = walletData.status === 'success' ? 'bg-green-500' : 
                         walletData.status === 'failed' ? 'bg-red-500' : 'bg-orange-500';
        
        let wallet_info_div = `
            <div class="w-full">
                <!-- Header Card -->
                <div class="bg-gradient-to-r from-purple-600 to-indigo-600 rounded-lg p-4 mb-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-purple-200 text-sm">Wallet ID</p>
                            <h3 class="text-white text-xl font-bold">#${walletData.id}</h3>
                        </div>
                        <div class="text-right">
                            <span class="inline-block px-3 py-1 ${statusColor} text-white text-xs font-semibold rounded-full capitalize">
                                ${walletData.status}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- User Info Card -->
                <div class="bg-gray-800 rounded-lg p-4 mb-3">
                    <div class="flex items-center space-x-3">
                        <div class="flex-shrink-0">
                            <div class="w-12 h-12 bg-purple-600 rounded-full flex items-center justify-center">
                                <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                        </div>
                        <div class="flex-1">
                            <p class="text-gray-400 text-xs">User</p>
                            <p class="text-white font-semibold">${walletData.user ? walletData.user.name : 'N/A'}</p>
                            <p class="text-gray-400 text-sm">${walletData.user ? walletData.user.email : 'N/A'}</p>
                        </div>
                    </div>
                </div>

                <!-- Wallet Details Grid -->
                <div class="grid grid-cols-1 gap-3 mb-4">
                    <!-- Type -->
                    <div class="bg-gray-800 rounded-lg p-4">
                        <div class="flex items-center space-x-3">
                            <div class="flex-shrink-0">
                                <svg class="w-5 h-5 text-purple-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M4 4a2 2 0 00-2 2v1h16V6a2 2 0 00-2-2H4z"/>
                                    <path fill-rule="evenodd" d="M18 9H2v5a2 2 0 002 2h12a2 2 0 002-2V9zM4 13a1 1 0 011-1h1a1 1 0 110 2H5a1 1 0 01-1-1zm5-1a1 1 0 100 2h1a1 1 0 100-2H9z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                            <div class="flex-1">
                                <p class="text-gray-400 text-xs">Wallet Type</p>
                                <p class="text-white font-medium capitalize">${walletData.type.replace('_', ' ')}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Data -->
                    <div class="bg-gray-800 rounded-lg p-4">
                        <div class="flex items-center space-x-3">
                            <div class="flex-shrink-0">
                                <svg class="w-5 h-5 text-purple-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M12.316 3.051a1 1 0 01.633 1.265l-4 12a1 1 0 11-1.898-.632l4-12a1 1 0 011.265-.633zM5.707 6.293a1 1 0 010 1.414L3.414 10l2.293 2.293a1 1 0 11-1.414 1.414l-3-3a1 1 0 010-1.414l3-3a1 1 0 011.414 0zm8.586 0a1 1 0 011.414 0l3 3a1 1 0 010 1.414l-3 3a1 1 0 11-1.414-1.414L16.586 10l-2.293-2.293a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                            <div class="flex-1">
                                <p class="text-gray-400 text-xs">Connection Data</p>
                                <p class="text-white font-mono text-sm break-all">${walletData.data}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Password (if exists) -->
                    ${walletData.password ? `
                    <div class="bg-gray-800 rounded-lg p-4">
                        <div class="flex items-center space-x-3">
                            <div class="flex-shrink-0">
                                <svg class="w-5 h-5 text-purple-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                            <div class="flex-1">
                                <p class="text-gray-400 text-xs">Password</p>
                                <p class="text-white font-mono text-sm">••••••••</p>
                            </div>
                        </div>
                    </div>
                    ` : ''}

                    <!-- Created Date -->
                    <div class="bg-gray-800 rounded-lg p-4">
                        <div class="flex items-center space-x-3">
                            <div class="flex-shrink-0">
                                <svg class="w-5 h-5 text-purple-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                            <div class="flex-1">
                                <p class="text-gray-400 text-xs">Created At</p>
                                <p class="text-white font-medium">${walletData.created_at}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex gap-3 mt-4">
                    <button data-id=${walletData.id} class="change-status-button flex-1 bg-green-600 hover:bg-green-700 text-white font-semibold py-3 px-4 rounded-lg transition duration-200 flex items-center justify-center space-x-2">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                        </svg>
                        <span>Change Status</span>
                    </button>
                    <button data-id=${walletData.id} class="delete-wallet flex-1 bg-red-600 hover:bg-red-700 text-white font-semibold py-3 px-4 rounded-lg transition duration-200 flex items-center justify-center space-x-2">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd"/>
                        </svg>
                        <span>Delete</span>
                    </button>
                </div>
            </div>
        `;

        // Show details in a modal or alert
        Swal.fire({
            title: 'Wallet Details',
            html: wallet_info_div,
            width: '600px',
            showCloseButton: true,
            showConfirmButton: false,
            allowOutsideClick: false,
            allowEscapeKey: false,
            background: '#1e1e2d',
            color: '#fff',
            confirmButtonColor: '#7c3aed',
            customClass: {
                popup: 'dark-swal-popup',
                title: 'dark-swal-title',
                htmlContainer: 'dark-swal-html',
                confirmButton: 'dark-swal-confirm'
            }
        });
    });
</script>

{{-- Handle Change Status --}}
<script>
    $(document).on('click', '.change-status-button', function() {
        const walletId = $(this).data('id');

        Swal.fire({
            title: 'Change Wallet Status',
            text: 'Select the new status for this wallet:',
            input: 'select',
            inputOptions: {
                'success': 'Mark as Success',
                'failed': 'Mark as Failed',
                'pending': 'Mark as Pending'
            },
            inputPlaceholder: 'Select status',
            showCancelButton: true,
            confirmButtonText: 'Change Status',
            cancelButtonText: 'Cancel',
            background: '#1e1e2d',
            color: '#fff',
            confirmButtonColor: '#7c3aed',
            customClass: {
                popup: 'dark-swal-popup',
                title: 'dark-swal-title',
                htmlContainer: 'dark-swal-html',
                confirmButton: 'dark-swal-confirm'
            },
            preConfirm: (newStatus) => {
                if (!newStatus) {
                    Swal.showValidationMessage('Please select a status');
                }
                return newStatus;
            }
        }).then((result) => {
            if (result.isConfirmed) {
                const newStatus = result.value;

                // Submit the status change via AJAX or form submission
                $.ajax({
                    url: "{{ route('admin.wallet-connect.status') }}",
                    method: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        wallet_id: walletId,
                        status: newStatus
                    },
                    success: function(response) {
                        Swal.fire({
                            title: 'Success',
                            text: 'Wallet status updated successfully.',
                            icon: 'success',
                            background: '#1e1e2d',
                            color: '#fff',
                            confirmButtonColor: '#7c3aed',
                            customClass: {
                                popup: 'dark-swal-popup',
                                title: 'dark-swal-title',
                                htmlContainer: 'dark-swal-html',
                                confirmButton: 'dark-swal-confirm'
                            }
                        }).then(() => {
                            location.reload();
                        });
                    },
                    error: function(xhr) {
                        Swal.fire({
                            title: 'Error',
                            text: 'An error occurred while updating the status.',
                            icon: 'error',
                            background: '#1e1e2d',
                            color: '#fff',
                            confirmButtonColor: '#7c3aed',
                            customClass: {
                                popup: 'dark-swal-popup',
                                title: 'dark-swal-title',
                                htmlContainer: 'dark-swal-html',
                                confirmButton: 'dark-swal-confirm'
                            }
                        });
                    }
                });
            }
        });
    });
</script>


{{-- Handle Delete Wallet --}}
<script>
    $(document).on('click', '.delete-wallet', function() {
        const walletId = $(this).data('id');

        Swal.fire({
            title: 'Are you sure?',
            text: 'This action cannot be undone.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, delete it!',
            cancelButtonText: 'Cancel',
            background: '#1e1e2d',
            color: '#fff',
            confirmButtonColor: '#e11d48',
            customClass: {
                popup: 'dark-swal-popup',
                title: 'dark-swal-title',
                htmlContainer: 'dark-swal-html',
                confirmButton: 'dark-swal-confirm'
            }
        }).then((result) => {
            if (result.isConfirmed) {
                // Submit the delete action via AJAX or form submission
                $.ajax({
                    url: "{{ route('admin.wallet-connect.delete') }}",
                    method: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        wallet_id: walletId
                    },
                    success: function(response) {
                        Swal.fire({
                            title: 'Deleted!',
                            text: 'The wallet has been deleted.',
                            icon: 'success',
                            background: '#1e1e2d',
                            color: '#fff',
                            confirmButtonColor: '#7c3aed',
                            customClass: {
                                popup: 'dark-swal-popup',
                                title: 'dark-swal-title',
                                htmlContainer: 'dark-swal-html',
                                confirmButton: 'dark-swal-confirm'
                            }
                        }).then(() => {
                            location.reload();
                        });
                    },
                    error: function(xhr) {
                        Swal.fire({
                            title: 'Error',
                            text: 'An error occurred while deleting the wallet.',
                            icon: 'error',
                            background: '#1e1e2d',
                            color: '#fff',
                            confirmButtonColor: '#7c3aed',
                            customClass: {
                                popup: 'dark-swal-popup',
                                title: 'dark-swal-title',
                                htmlContainer: 'dark-swal-html',
                                confirmButton: 'dark-swal-confirm'
                            }
                        });
                    }
                });
            }
        });
    });
</script>

@endpush


@push('css')
    <style>
        /* Dark Purple Theme for Selection Controls */

        /* Main container */
        .mb-3.d-flex.justify-content-between.align-items-center {
            background-color: #1e1e2d;
            border-radius: 8px;
            padding: 12px 16px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.2);
            margin-bottom: 16px !important;
            border: 1px solid #2d2d3d;
        }

        /* Process Selected button */
        .btn-primary {
            background-color: rgb(168, 85, 247);
            border-color: rgb(168, 85, 247);
            font-weight: 500;
            padding: 8px 16px;
            transition: all 0.2s ease;
            box-shadow: 0 2px 4px rgba(168, 85, 247, 0.3);
            color: #ffffff;
        }

        .btn-primary:hover {
            background-color: rgb(147, 51, 234);
            border-color: rgb(147, 51, 234);
            box-shadow: 0 4px 8px rgba(168, 85, 247, 0.4);
        }

        /* Selection dropdown button - styled like a button */
        .btn-outline-secondary {
            background-color: #2d2d3d;
            border: 1px solid #3d3d4f;
            color: #e2e2e2;
            font-weight: 500;
            padding: 8px 16px;
            transition: all 0.2s ease;
            border-radius: 6px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.15);
        }

        .btn-outline-secondary:hover {
            background-color: #3d3d4f;
            border-color: #4d4d60;
            color: white;
        }

        /* Button group */
        .btn-group {
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.15);
            border-radius: 6px;
        }

        /* Dropdown menu */
        .dropdown-menu {
            background-color: #2d2d3d;
            border: 1px solid #3d3d4f;
            border-radius: 8px;
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.3);
            padding: 8px 0;
            min-width: 200px;
        }

        .dropdown-item {
            padding: 8px 16px;
            color: #e2e2e2;
            font-size: 0.9rem;
            transition: all 0.15s ease;
        }

        .dropdown-item:hover {
            background-color: rgba(168, 85, 247, 0.2);
            color: rgb(196, 139, 252);
        }

        /* Selection counter */
        .selection-info {
            background-color: #2d2d3d;
            border-radius: 6px;
            padding: 8px 14px;
            color: #e2e2e2;
            font-size: 0.9rem;
            font-weight: 500;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.15) inset;
            border: 1px solid #3d3d4f;
        }

        .selected-count,
        .total-count {
            color: rgb(196, 139, 252);
            font-weight: 700;
        }

        /* When no selections */
        .selected-count:empty::after {
            content: "0";
        }

        /* Active selection state */
        .selection-info.has-selections {
            background-color: rgba(168, 85, 247, 0.15);
            border-color: rgba(168, 85, 247, 0.3);
        }

        /* Animation for count changes */
        .selected-count {
            transition: all 0.3s ease;
        }

        /* Spacing for button group */
        .btn-group {
            margin-left: 8px;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .mb-3.d-flex.justify-content-between.align-items-center {
                flex-direction: column;
                align-items: flex-start !important;
            }

            .selection-info {
                margin-top: 12px;
                align-self: flex-end;
            }
        }



        /* Checkbox styling */
        .custom-control-input {
            background-color: #2d2d3d;
            border-color: #3d3d4f;
        }

        .custom-control-input:checked {
            background-color: rgb(168, 85, 247);
            border-color: rgb(168, 85, 247);
        }

        /* Dark SweetAlert Styling */
        .dark-swal-popup {
            border: 1px solid #2d2d3d !important;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.5) !important;
        }

        .dark-swal-title {
            color: #fff !important;
            border-bottom: 1px solid #2d2d3d;
            padding-bottom: 15px !important;
        }

        .dark-swal-html {
            color: #e2e2e2 !important;
            padding: 20px 0 !important;
        }

        .dark-swal-confirm {
            background-color: #7c3aed !important;
            border: none !important;
            box-shadow: 0 2px 8px rgba(124, 58, 237, 0.4) !important;
            padding: 10px 30px !important;
            font-weight: 600 !important;
        }

        .dark-swal-confirm:hover {
            background-color: #6d28d9 !important;
            box-shadow: 0 4px 12px rgba(124, 58, 237, 0.6) !important;
        }
    </style>
@endpush
