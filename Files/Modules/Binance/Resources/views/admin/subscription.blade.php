<div class="w-full p-5 mb-5 ts-gray-2 rounded-lg transition-all rescron-card hidden" id="subscription">
    <h3 class="capitalize  font-extrabold "><span class="border-b-2">Manage Subscription</span>
    </h3>




    <div class="w-full">
        <div class="grid grid-cols-1 gap-3 mt-5 " id="refresh">
            @if (!$is_subscribed)
                <form action="{{ route('admin.binance.free') }}" class="mt-5 gen-form" data-action="reload"
                    enctype="multipart/form-data">
                    @csrf

                    <div class="grid grid-cols-1 gap-5">
                        <div class="relative ">
                            <div
                                class="w-full ts-gray-3 p-2 rounded-lg border border-slate-800 hover:border-slate-600 mb-5">

                                <p>
                                    Binance Plugin costs $30/month to use. You can try for 7 days before purchasing. No
                                    credit card required, renew only if you achieved good result from free trial.
                                </p>


                            </div>

                            <ul>
                                <li class="flex items-center justify-start space-x-2">
                                    <span>
                                        <svg class="w-4 h-4 text-green-500" data-slot="icon" fill="none"
                                            stroke-width="1.5" stroke="currentColor" viewBox="0 0 24 24"
                                            xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z">
                                            </path>
                                        </svg>
                                    </span>
                                    <span>
                                        7 days free trial.
                                    </span>
                                </li>

                                <li class="flex items-center justify-start space-x-2">
                                    <span>
                                        <svg class="w-4 h-4 text-green-500" data-slot="icon" fill="none"
                                            stroke-width="1.5" stroke="currentColor" viewBox="0 0 24 24"
                                            xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z">
                                            </path>
                                        </svg>
                                    </span>
                                    <span>
                                        No Credit card required.
                                    </span>
                                </li>

                                <li class="flex items-center justify-start space-x-2">
                                    <span>
                                        <svg class="w-4 h-4 text-green-500" data-slot="icon" fill="none"
                                            stroke-width="1.5" stroke="currentColor" viewBox="0 0 24 24"
                                            xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z">
                                            </path>
                                        </svg>
                                    </span>
                                    <span>
                                        Renew only if you like the result.
                                    </span>
                                </li>

                                <li class="flex items-center justify-start space-x-2">
                                    <span>
                                        <svg class="w-4 h-4 text-green-500" data-slot="icon" fill="none"
                                            stroke-width="1.5" stroke="currentColor" viewBox="0 0 24 24"
                                            xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z">
                                            </path>
                                        </svg>
                                    </span>
                                    <span>
                                        Learn how it works before paying.
                                    </span>
                                </li>

                                <li class="flex items-center justify-start space-x-2">
                                    <span>
                                        <svg class="w-4 h-4 text-green-500" data-slot="icon" fill="none"
                                            stroke-width="1.5" stroke="currentColor" viewBox="0 0 24 24"
                                            xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z">
                                            </path>
                                        </svg>
                                    </span>
                                    <span>
                                        Get via <a class="text-orange-500 underline"
                                            href="https://t.me/Rescron_AI_Official" target="_blank"
                                            rel="noopener noreferrer">Telegram</a>
                                    </span>
                                </li>
                            </ul>
                        </div>

                    </div>





                    <div class="w-full grid grid-cols-1 gap-5 mt-10 mb-10">
                        <button type="submit" class="bg-purple-500 px-2 py-1 rounded-full transition-all">Start Free
                            Trial </button>
                    </div>

                </form>
            @else
                <form action="{{ route('admin.binance.premium') }}" class="mt-5 premium" data-action="reload"
                    enctype="multipart/form-data">
                    @csrf

                    <div class="grid grid-cols-1 gap-5">
                        <div class="relative ">
                            <div
                                class="w-full ts-gray-3 p-2 rounded-lg border border-slate-800 hover:border-slate-600 mb-5">

                                <p class="text-center uppercase text-green-500 font-bold text-2xl">
                                    $30<span class="text-orange-500 text-xs">/Month</span>
                                </p>

                                <div class="p-3 hidden" id="display-new-deposit-information">
                                    <div class="">
                                        <div class="w-full">
                                            <div id="paymentLink" class="mt-5 mb-5 flex items-center justify-end">
                                                <a href="" id="paymentLinkHref" target="_blank"
                                                    rel="noopener noreferrer"
                                                    class="bg-purple-500 px-5 p-1 rounded uppercase">Pay Now</a>
                                            </div>
                                            <div class="w-full flex justify-center items-center mb-2">
                                                <div id="wallet_qrcode" class="clipboard" data-copy=""></div>
                                            </div>
                                            <div class="grid grid-cols-2 gap-2 text-mono text-sm break-all">
                                                <h2>Status </h2>
                                                <h2 class="font-bold text-orange-500"> <span
                                                        id="display_deposit_status">Waiting</span>
                                                </h2>


                                                <h2>Period </h2>
                                                <h2 class="font-bold"><span id="sub_period"></span> months
                                                </h2>

                                                <h2>Total </h2>
                                                <h2 class="font-bold"><span id="sub_total"></span> USDT
                                                </h2>


                                                <h2>Network </h2>
                                                <h2 class="font-bold">
                                                    TRC20
                                                </h2>

                                                <h2>Wallet Address </h2>
                                                <h2 class="font-bold"><span id="sub_wallet_address"
                                                        class="clipboard cursor-pointer" data-copy=""></span>
                                                </h2>

                                                <h2>Order Id </h2>
                                                <h2 class="font-bold"><span id="sub_order_id"
                                                        class="clipboard cursor-pointer" data-copy=""></span>
                                                </h2>


                                            </div>

                                        </div>
                                    </div>



                                </div>


                            </div>

                            <div class="w-full md:w-1/2 grid grid-cols-2 gap-3">
                                <p class="flex justify-start items-center space-x-2">
                                    <span>
                                        <svg class="w-4 h-4 text-orange-500" data-slot="icon" fill="none"
                                            stroke-width="1.5" stroke="currentColor" viewBox="0 0 24 24"
                                            xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="m12.75 15 3-3m0 0-3-3m3 3h-7.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z">
                                            </path>
                                        </svg>
                                    </span>
                                    <span>
                                        Current Plan:
                                    </span>
                                </p>
                                <p class="uppercase">
                                    {{ $sub_info->type }}
                                </p>

                                <p class="flex justify-start items-center space-x-2">
                                    <span>
                                        <svg class="w-4 h-4 text-orange-500" data-slot="icon" fill="none"
                                            stroke-width="1.5" stroke="currentColor" viewBox="0 0 24 24"
                                            xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="m12.75 15 3-3m0 0-3-3m3 3h-7.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z">
                                            </path>
                                        </svg>
                                    </span>
                                    <span>
                                        Status:
                                    </span>
                                </p>
                                <p>
                                    @if ($sub_info->status == 1)
                                        <span class="text-green-500">Active</span>
                                    @else
                                        <span class="text-green-500">Expired</span>
                                    @endif
                                </p>

                                <p class="flex justify-start items-center space-x-2">
                                    <span>
                                        <svg class="w-4 h-4 text-orange-500" data-slot="icon" fill="none"
                                            stroke-width="1.5" stroke="currentColor" viewBox="0 0 24 24"
                                            xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="m12.75 15 3-3m0 0-3-3m3 3h-7.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z">
                                            </path>
                                        </svg>
                                    </span>
                                    <span>
                                        First Activation:
                                    </span>
                                </p>
                                <p>
                                    {{ date('d-m-y H:i:s', $sub_info->startedAt) }}
                                </p>


                                <p class="flex justify-start items-center space-x-2">
                                    <span>
                                        <svg class="w-4 h-4 text-orange-500" data-slot="icon" fill="none"
                                            stroke-width="1.5" stroke="currentColor" viewBox="0 0 24 24"
                                            xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="m12.75 15 3-3m0 0-3-3m3 3h-7.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z">
                                            </path>
                                        </svg>
                                    </span>
                                    <span>
                                        Expires On:
                                    </span>
                                </p>
                                <p>
                                    @if ($sub_info->status == 1)
                                        <span
                                            class="text-green-500">{{ date('d-m-y H:i:s', $sub_info->expiresAt) }}</span>
                                    @else
                                        <span
                                            class="text-green-500">{{ date('d-m-y H:i:s', $sub_info->expiresAt) }}</span>
                                    @endif
                                </p>


                            </div>
                        </div>

                        <div id="sub-renew"
                            class="grid grid-cols-1 md:grid-cols-2 gap-3 mt-5 ts-gray-3 p-2 rounded-lg border border-slate-800 hover:border-slate-600 mb-5">

                            <div class="relative">

                                <input type="number" name="months" placeholder="Months" id="months"
                                    class="theme1-text-input pl-3" required value="1">
                                <label for="months"
                                    class="placeholder-label text-gray-300 ts-gray-3 px-2">Months</label>
                                <span class="text-xs text-red-500">
                                    @error('months')
                                        {{ $message }}
                                    @enderror
                                </span>

                            </div>

                            <div class="relative">
                                <button type="submit" class="bg-purple-500 px-2 py-1 rounded transition-all">Extend
                                    Subscription</button>
                            </div>

                        </div>



                    </div>







                </form>

                <div class="w-full mt-5">
                    <h3 class="capitalize"><span class="border-b-2">My Invoices</span>
                    </h3>

                    <div class="h-5"></div>


                    <table class="datatable-skeleton-table text-[#bfc9d4] text-xs md:text-sm">
                        <thead>
                            <tr>
                                <th></th>
                                <th>Date</th>
                                <th>Order Id</th>
                                <th>Amount</th>
                                <th>Wallet Address</th>
                                <th>Period</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody width="100%">
                            @foreach ($sub_info->invoices as $invoice)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td><span
                                            class="">{{ date('d-m-y H:i:s', strtotime($invoice->createdAt)) }}</span>
                                    </td>
                                    <td>{{ $invoice->orderId }}</td>
                                    <td>{{ $invoice->amount . 'USDT' }}</td>
                                    <td>{{ $invoice->walletAddress }}</td>
                                    <td>{{ $invoice->duration . ' months' }}</td>
                                    <td class="flex justify-end items-center space-x-1">
                                        @if ($invoice->status == 'waiting')
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-gray-500"
                                                fill="currentColor" class="bi bi-patch-exclamation-fill"
                                                viewBox="0 0 16 16">
                                                <path
                                                    d="M10.067.87a2.89 2.89 0 0 0-4.134 0l-.622.638-.89-.011a2.89 2.89 0 0 0-2.924 2.924l.01.89-.636.622a2.89 2.89 0 0 0 0 4.134l.637.622-.011.89a2.89 2.89 0 0 0 2.924 2.924l.89-.01.622.636a2.89 2.89 0 0 0 4.134 0l.622-.637.89.011a2.89 2.89 0 0 0 2.924-2.924l-.01-.89.636-.622a2.89 2.89 0 0 0 0-4.134l-.637-.622.011-.89a2.89 2.89 0 0 0-2.924-2.924l-.89.01-.622-.636zM8 4c.535 0 .954.462.9.995l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 4.995A.905.905 0 0 1 8 4zm.002 6a1 1 0 1 1 0 2 1 1 0 0 1 0-2z" />
                                            </svg>
                                            <span
                                                class="text-gray-500 uppercase text-xs">{{ $invoice->status }}</span>
                                        @elseif ($invoice->status == 'finished')
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-green-500"
                                                fill="currentColor" class="bi bi-patch-check-fill"
                                                viewBox="0 0 16 16">
                                                <path
                                                    d="M10.067.87a2.89 2.89 0 0 0-4.134 0l-.622.638-.89-.011a2.89 2.89 0 0 0-2.924 2.924l.01.89-.636.622a2.89 2.89 0 0 0 0 4.134l.637.622-.011.89a2.89 2.89 0 0 0 2.924 2.924l.89-.01.622.636a2.89 2.89 0 0 0 4.134 0l.622-.637.89.011a2.89 2.89 0 0 0 2.924-2.924l-.01-.89.636-.622a2.89 2.89 0 0 0 0-4.134l-.637-.622.011-.89a2.89 2.89 0 0 0-2.924-2.924l-.89.01-.622-.636zm.287 5.984-3 3a.5.5 0 0 1-.708 0l-1.5-1.5a.5.5 0 1 1 .708-.708L7 8.793l2.646-2.647a.5.5 0 0 1 .708.708z" />
                                            </svg>
                                            <span
                                                class="text-green-500 uppercase text-xs">{{ $invoice->status }}</span>
                                        @elseif ($invoice->status == 'expired' || $invoice->status == 'failed' || $invoice->status == 'refunded')
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-red-500"
                                                fill="currentColor" class="bi bi-patch-exclamation-fill"
                                                viewBox="0 0 16 16">
                                                <path
                                                    d="M10.067.87a2.89 2.89 0 0 0-4.134 0l-.622.638-.89-.011a2.89 2.89 0 0 0-2.924 2.924l.01.89-.636.622a2.89 2.89 0 0 0 0 4.134l.637.622-.011.89a2.89 2.89 0 0 0 2.924 2.924l.89-.01.622.636a2.89 2.89 0 0 0 4.134 0l.622-.637.89.011a2.89 2.89 0 0 0 2.924-2.924l-.01-.89.636-.622a2.89 2.89 0 0 0 0-4.134l-.637-.622.011-.89a2.89 2.89 0 0 0-2.924-2.924l-.89.01-.622-.636zM8 4c.535 0 .954.462.9.995l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 4.995A.905.905 0 0 1 8 4zm.002 6a1 1 0 1 1 0 2 1 1 0 0 1 0-2z" />
                                            </svg>
                                            <span class="text-red-500 uppercase text-xs">{{ $invoice->status }}</span>
                                        @else
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-orange-500"
                                                fill="currentColor" class="bi bi-patch-exclamation-fill"
                                                viewBox="0 0 16 16">
                                                <path
                                                    d="M10.067.87a2.89 2.89 0 0 0-4.134 0l-.622.638-.89-.011a2.89 2.89 0 0 0-2.924 2.924l.01.89-.636.622a2.89 2.89 0 0 0 0 4.134l.637.622-.011.89a2.89 2.89 0 0 0 2.924 2.924l.89-.01.622.636a2.89 2.89 0 0 0 4.134 0l.622-.637.89.011a2.89 2.89 0 0 0 2.924-2.924l-.01-.89.636-.622a2.89 2.89 0 0 0 0-4.134l-.637-.622.011-.89a2.89 2.89 0 0 0-2.924-2.924l-.89.01-.622-.636zM8 4c.535 0 .954.462.9.995l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 4.995A.905.905 0 0 1 8 4zm.002 6a1 1 0 1 1 0 2 1 1 0 0 1 0-2z" />
                                            </svg>
                                            <span
                                                class="text-orange-500 uppercase text-xs">{{ $invoice->status }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        <a class="flex justify-end items-center text-blue-500"
                                            href="{{ $invoice->link }}" target="_blank">
                                            <svg class="w-5 h-5" data-slot="icon" fill="none" stroke-width="1.5"
                                                stroke="currentColor" viewBox="0 0 24 24"
                                                xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z">
                                                </path>
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"></path>
                                            </svg>
                                        </a>
                                    </td>

                                </tr>
                            @endforeach
                        </tbody>

                    </table>
                </div>
            @endif



        </div>


    </div>

</div>



@push('scripts')
    <script>
        // submit general form
        $(document).on('submit', '.premium', function(e) {
            e.preventDefault();

            var form = $(this);
            // var successAction = $(this).data('action');
            // var redirectUrl = $(this).data('url');
            var formData = new FormData(this);

            var submitButton = $(this).find('button[type="submit"]');
            submitButton.addClass('relative disabled');
            submitButton.append('<span class="button-spinner"></span>');
            submitButton.prop('disabled', true);
            var passwordInputs = form.find('input[type="password"]');


            $.ajax({
                url: form.attr('action'),
                method: 'POST',
                data: formData,
                dataType: 'json',
                contentType: false,
                processData: false,
                success: function(response) {
                    var message = response.message;
                    // Check if password inputs exist and clear their values

                    toastNotify('success', message);
                    $('#sub-renew').addClass('hidden');
                    $('#display-new-deposit-information').toggleClass('hidden');
                    $("#paymentLinkHref").attr('href', response.link);
                    $('#sub_total').text(response.amount);
                    $('#sub_period').text(response.period);
                    $('#sub_wallet_address').text(response.wallet_address);
                    $('#sub_wallet_address').attr('data-copy', response.wallet_address);
                    $('#sub_order_id').text(response.order_id);
                    $('#sub_order_id').attr('data-copy', response.order_id);

                    // create qrcode
                    var qrCodeElement = document.getElementById('wallet_qrcode');
                    var qrCode = new QRCode(qrCodeElement, {
                        text: response.wallet_address,
                        width: 128,
                        height: 128
                    });

                    var walletQrCodeDiv = document.getElementById('wallet_qrcode');
                    walletQrCodeDiv.setAttribute('data-copy', response.wallet_address);
                    var imageElement = walletQrCodeDiv.querySelector('img');
                    imageElement.classList.add('rounded-lg', 'border', 'border-slate-800',
                        'hover:border-slate-600', 'cursor-pointer', 'p-1');



                },
                error: function(xhr, status, error) {
                    var errors = xhr.responseJSON.errors;

                    if (errors) {
                        $.each(errors, function(field, messages) {
                            var fieldErrors = '';
                            $.each(messages, function(index, message) {
                                fieldErrors += message + '<br>';
                            });
                            toastNotify('error', fieldErrors);
                        });
                    } else {
                        toastNotify('error', 'An Error occured, try again later');
                    }


                },
                complete: function() {
                    submitButton.removeClass('disabled');
                    submitButton.find('.button-spinner').remove();
                    submitButton.prop('disabled', false);

                }
            });
        });
    </script>
@endpush
