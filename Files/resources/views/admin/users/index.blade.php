@extends('layouts.admin')

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
    </style>
@endpush

@section('contents')
    <div class="w-full p-3" id="refresh">
        <div class="w-full lg:flex lg:gap-3">
            <div class="w-full lg:w-1/3 h-72 ts-gray-2 rounded-lg p-5 mb-3">
                <div class="w-full grid grid-cols-1 gap-3 p-2">
                    {{-- total users --}}

                    <div
                        class="w-full flex items-center  ts-gray-2 rounded-lg p-3 border border-slate-800 hover:border-slate-600 transition-all">
                        <div class="w-full flex items-center justify-between">
                            <div class="w-full">
                                <div class="w-full mb-1 flex justify-between items-center">
                                    <div class="ts-gray-3 text-purple-500 rounded-full p-2 w-8 h-8">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="currentColor"
                                            class="bi bi-people-fill" viewBox="0 0 16 16">
                                            <path
                                                d="M7 14s-1 0-1-1 1-4 5-4 5 3 5 4-1 1-1 1H7Zm4-6a3 3 0 1 0 0-6 3 3 0 0 0 0 6Zm-5.784 6A2.238 2.238 0 0 1 5 13c0-1.355.68-2.75 1.936-3.72A6.325 6.325 0 0 0 5 9c-4 0-5 3-5 4s1 1 1 1h4.216ZM4.5 8a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5Z" />
                                        </svg>
                                    </div>
                                    <p class=" font-bold text-gray-500">Total Users</p>
                                    <p><span
                                            class="px-2 py-1 ts-gray-3 rounded-full ">{{ number_format($user_query->count()) }}</span>
                                    </p>
                                </div>

                                <div class="w-full font-mono mt-2">

                                    <p class="w-fulll flex justify-between items-center text-xs">
                                        Email:
                                        <span class="flex items-center space-x-1">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-2 h-2 text-green-500"
                                                fill="currentColor" class="bi bi-circle-fill" viewBox="0 0 16 16">
                                                <circle cx="8" cy="8" r="8" />
                                            </svg>
                                            <span>{{ number_format($user_query->whereNotNull('email_verified_at')->count()) }}</span>
                                        </span>
                                        <span class="flex items-center space-x-1">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-2 h-2 text-orange-500"
                                                fill="currentColor" class="bi bi-circle-fill" viewBox="0 0 16 16">
                                                <circle cx="8" cy="8" r="8" />
                                            </svg>
                                            <span>{{ number_format($user_query->whereNull('email_verified_at')->count()) }}</span>
                                        </span>

                                    </p>

                                    <p class="w-fulll flex justify-between items-center text-xs">
                                        KYC:
                                        <span class="flex items-center space-x-1">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-2 h-2 text-green-500"
                                                fill="currentColor" class="bi bi-circle-fill" viewBox="0 0 16 16">
                                                <circle cx="8" cy="8" r="8" />
                                            </svg>
                                            <span>{{ number_format($user_query->whereNotNull('kyc_verified_at')->count()) }}</span>
                                        </span>
                                        <span class="flex items-center space-x-1">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-2 h-2 text-orange-500"
                                                fill="currentColor" class="bi bi-circle-fill" viewBox="0 0 16 16">
                                                <circle cx="8" cy="8" r="8" />
                                            </svg>
                                            <span>{{ number_format($user_query->whereNull('kyc_verified_at')->count()) }}</span>
                                        </span>

                                    </p>


                                </div>
                            </div>

                        </div>

                    </div>



                    {{-- balance --}}
                    <div
                        class="w-full flex items-center ts-gray-2 rounded-lg p-3 border border-slate-800 hover:border-slate-600 transition-all">
                        <div class="w-full flex items-center justify-between">
                            <div>
                                <div class="mb-1">
                                    <p class=" font-bold text-gray-500">Cummulative Bal.</p>
                                </div>

                                <div class="flex items-center justify-between font-mono">
                                    <div class="ts-gray-3 text-purple-500 rounded-full p-2 w-8 h-8">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="currentColor"
                                            class="bi bi-people-fill" viewBox="0 0 16 16">
                                            <path
                                                d="M7 14s-1 0-1-1 1-4 5-4 5 3 5 4-1 1-1 1H7Zm4-6a3 3 0 1 0 0-6 3 3 0 0 0 0 6Zm-5.784 6A2.238 2.238 0 0 1 5 13c0-1.355.68-2.75 1.936-3.72A6.325 6.325 0 0 0 5 9c-4 0-5 3-5 4s1 1 1 1h4.216ZM4.5 8a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5Z" />
                                        </svg>
                                    </div>

                                    <span>{{ formatAmount($user_query->sum('balance')) }}</span>
                                </div>
                            </div>

                        </div>

                    </div>


                </div>
            </div>
            <div class="w-full lg:w-2/3">
                <div class="w-full p-5 mb-5 ts-gray-2 rounded-lg transition-all rescron-card" id="users">
                    <h3 class="capitalize  font-extrabold "><span class="border-b-2">Users</span>
                    </h3>

                    <div class="w-full flex items-center justify-center mt-5">

                    
                    <div class=" ts-gray-1 p-5 rounded-lg w-full md:w-1/3">
                        <div class="flex justify-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-10 h-10 text-green-500"
                            fill="currentColor" class="bi bi-info-circle" viewBox="0 0 16 16">
                            <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z" />
                            <path
                                d="m8.93 6.588-2.29.287-.082.38.45.083c.294.07.352.176.288.469l-.738 3.468c-.194.897.105 1.319.808 1.319.545 0 1.178-.252 1.465-.598l.088-.416c-.2.176-.492.246-.686.246-.275 0-.375-.193-.304-.533L8.93 6.588zM9 4.5a1 1 0 1 1-2 0 1 1 0 0 1 2 0z" />
                        </svg>
                        </div>
                        <div class="text-center">
                            
                            <p class=" text-gray-500 mt-2 ">
                                <span class="uppercase text-green-500">New: </span>
                                Loser Mode added. When loser mode is enabled, all trades by the user will result in loss.
                            </p>
                        </div>

                    </div>
                </div>

                    <div class="w-full mt-5">
                        <form action="{{ route('admin.users.action') }}" method="POST" id="usersForm" class="gen-form" data-action="redirect" data-url="{{ route('admin.users.index') }}">
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
                                                <li><a class="dropdown-item select-all-pages"
                                                        href="javascript:void(0);">Select All (All Pages)</a></li>
                                                <li><a class="dropdown-item deselect-all"
                                                        href="javascript:void(0);">Deselect All</a></li>
                                            </ul>
                                        </div>
                                    </div>

                                    <!-- Selection counter -->
                                    <div class="selection-info mt-3">
                                        <span class="selected-count">0</span> of <span class="total-count">0</span> users
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
                                            <th>Name</th>
                                            <th>Email</th>
                                            <th>Username</th>
                                            <th>Balance</th>
                                            <th>Status</th>
                                            <th>Loser Mode</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($users as $user)
                                            <tr>
                                                <td>
                                                    <div class="custom-control custom-checkbox">
                                                        <input type="checkbox" class="custom-control-input check-item"
                                                            id="check{{ $user->id }}" value="{{ $user->id }}">
                                                        <label class="custom-control-label"
                                                            for="check{{ $user->id }}"></label>
                                                    </div>
                                                </td>
                                                <th>{{ $loop->iteration }}</th>
                                                <td>{{ $user->name ?? 'Not Set' }}</td>
                                                <td>{{ $user->email }}</td>
                                                <td>{{ $user->username ?? 'Not Set' }}</td>
                                                <td> {{ formatAmount($user->balance) }}</td>
                                                <td>
                                                    @if ($user->status == 1)
                                                        <span class="text-green-500">Active</span>
                                                    @else
                                                        <span class="text-red-500">Suspended</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if ($user->loser == 1)
                                                        <span class="text-red-500">Active</span>
                                                    @else
                                                        <span class="text-green-500">Disabled</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    <a href="{{ route('admin.users.view', ['id' => $user->id]) }}"
                                                        class="view-single-user flex space-x-1 items-center text-gray-300  hover:scale-110 transition-all hover:text-white bg-purple-500 px-2 py-1 rounded-full text-xs">
                                                        <svg xmlns="http://www.w3.org/2000/svg" width="16"
                                                            height="16" fill="currentColor" class="bi bi-eye-fill"
                                                            viewBox="0 0 16 16">
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
                                <select id="actionSelect"  name="selected_action" class="theme1-text-input w-56">
                                    <option disabled selected>Choose Action
                                    </option>
                                    <option value="suspend">Suspend</option>
                                    <option value="activate">Re-activate</option>
                                    <option value="loser_mode_on">Enable Loser Mode</option>
                                    <option value="loser_mode_off">Disable Loser Mode</option>
                                    <option value="email">Send Bulk Email</option>
                                    <option value="delete">Delete</option>
                                </select>
                            </div>
                            <button type="submit" id="genSubmitButton" class="hidden">Submit</button>
                        </form>
                        
                    </div>



                </div>



            </div>

        </div>
    </div>
@endsection


@section('scripts')
    <script>
        $(document).on('change', '#search-users-input', function(e) {
            var base_url = "{{ route('admin.users.index') }}";
            var url_query = $(this).val();
            var params = new URLSearchParams();
            params.append('s', url_query);
            var formed_url = base_url + '?' + params.toString();
            $('#search-users-url').attr('href', formed_url);
        });
    </script>

    <script>
        $(document).ready(function() {
            // Store reference to DataTable
            let dataTable;

            // Store all user IDs for cross-page selection
            const allUserIds = [];

            // Collect all user IDs from the table
            $('.check-item').each(function() {
                allUserIds.push($(this).val());
            });

            // Update total count display
            $('.total-count').text(allUserIds.length);

            // Selected IDs array to track selections across pages
            let selectedIds = [];

            // Function to update hidden input with selected IDs
            function updateSelectedIds() {
                // Join IDs with comma and store in hidden input
                $('#selectedIds').val(selectedIds.join(','));

                // Update selection counter
                $('.selected-count').text(selectedIds.length);

                // Enable/disable submit button based on selection
                $('button[type="submit"]').prop('disabled', selectedIds.length === 0);
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

            // // Form submission - optional validation
            // $('#usersForm').on('submit', function(e) {
            //     if (selectedIds.length === 0) {
            //         e.preventDefault();
            //         alert('Please select at least one user.');
            //         return false;
            //     }
            //     return true;
            // });

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

    {{-- manage actions --}}
    <script>
        $(document).on('change', '#actionSelect', function(e) {
            let action = $(this).val();
            let selectedIds = $('#selectedIds').val();
            if (!selectedIds) {
                toastNotify('error', 'You have not selected any users yet');
                $(this).val('');
                return;
            }
            const allowed_actions = [
                'suspend', 'activate', 'loser_mode_on', 'loser_mode_off', 'email', 'delete'
            ];

            // Check if action is in the allowed_actions array
            if (!allowed_actions.includes(action)) {
                toastNotify('error', 'Action not recognized');
                return; // Stop execution of the function
            }

            // Configure confirmation messages based on action
            let title, text, icon, confirmButtonText;

            switch (action) {
                case 'suspend':
                    title = 'Suspend Users?';
                    text = 'Selected users will be suspended and unable to access the system.';
                    icon = 'warning';
                    confirmButtonText = 'Yes, Suspend';
                    break;
                case 'activate':
                    title = 'Activate Users?';
                    text = 'Selected users will be activated and gain access to the system.';
                    icon = 'info';
                    confirmButtonText = 'Yes, Activate';
                    break;
                case 'loser_mode_on':
                    title = 'Enable Loser Mode?';
                    text = 'Loser mode will be enabled for the selected users.';
                    icon = 'question';
                    confirmButtonText = 'Yes, Enable';
                    break;
                case 'loser_mode_off':
                    title = 'Disable Loser Mode?';
                    text = 'Loser mode will be disabled for the selected users.';
                    icon = 'question';
                    confirmButtonText = 'Yes, Disable';
                    break;
                case 'email':
                    title = 'Send Email?';
                    text = 'An email will be sent to all selected users.';
                    icon = 'info';
                    confirmButtonText = 'Yes, Send Email';
                    break;
                case 'delete':
                    title = 'Delete Users?';
                    text = 'This action cannot be undone. Selected users will be permanently removed.';
                    icon = 'error';
                    confirmButtonText = 'Yes, Delete';
                    break;
            }

            // Show SweetAlert confirmation dialog
            Swal.fire({
                title: title,
                text: text,
                icon: icon,
                showCancelButton: true,
                confirmButtonColor: action === 'delete' ? '#d33' : '#3085d6',
                cancelButtonColor: '#6c757d',
                confirmButtonText: confirmButtonText,
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    if (action == 'email') {
                        

                        // update the action url
                        let email_url = "{{ route('admin.users.email') }}";
                        $('#usersForm').attr('data-url', email_url);
                        
                    }
                    $('#genSubmitButton').click();
                } else {
                    // Reset the select dropdown to default
                    $('#actionSelect').val('');
                }
            });
        });
    </script>
@endsection
