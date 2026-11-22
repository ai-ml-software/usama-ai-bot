<div class="w-full p-5 mb-5 ts-gray-2 rounded-lg transition-all rescron-card hidden" id="pricing">
    <h3 class="capitalize   flex justify-between">
        <span class="border-b-2 font-extrabold">Pricing</span>

    </h3>




    <div class="w-full">
        <div class="grid grid-cols-1 gap-3 mt-5">

            <div class="text-xs ts-gray-1 font-mono text-gray-500 p-3 rounded-lg">
                <div class="flex  space-x-2 items-center mb-3 text-orange-500">
                    <div>
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-orange-500" fill="currentColor"
                            class="bi bi-info-circle" viewBox="0 0 16 16">
                            <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z" />
                            <path
                                d="m8.93 6.588-2.29.287-.082.38.45.083c.294.07.352.176.288.469l-.738 3.468c-.194.897.105 1.319.808 1.319.545 0 1.178-.252 1.465-.598l.088-.416c-.2.176-.492.246-.686.246-.275 0-.375-.193-.304-.533L8.93 6.588zM9 4.5a1 1 0 1 1-2 0 1 1 0 0 1 2 0z" />
                        </svg>
                    </div>
                    <p>
                        Starting April 1, 2025, Rescron AI will transition to an annual subscription model at . All
                        purchases made before March 1, 2025, must be upgraded to the annual plan to avoid
                        service interruption.
                    </p>

                </div>
            </div>

            <div class="grid grid-cols-1 gap-3">
                <h4 class="mb-2 uppercase text-blue-500 font-mono">Price Guide</h4>




                @foreach ($pricing_guide as $item)
                    <div class="grid grid-cols-2">
                        <div class="flex item-center justify-start space-x-1">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" class="w-6 h-6"
                                viewBox="0 0 16 16">
                                <path
                                    d="M12.354 4.354a.5.5 0 0 0-.708-.708L5 10.293 1.854 7.146a.5.5 0 1 0-.708.708l3.5 3.5a.5.5 0 0 0 .708 0l7-7zm-4.208 7-.896-.897.707-.707.543.543 6.646-6.647a.5.5 0 0 1 .708.708l-7 7a.5.5 0 0 1-.708 0z" />
                                <path d="m5.354 7.146.896.897-.707.707-.897-.896a.5.5 0 1 1 .708-.708z" />
                            </svg>
                            <p>{{ $item->service }}</p>
                        </div>
                        <div>
                            <p>{{ $item->price }}</p>
                        </div>
                    </div>
                @endforeach

                <p class="mt-5">Contact us via telegram <a class="underline text-orange-500" href="https://t.me/rescron" target="_blank" rel="noopener noreferrer">@rescron</a> or Email: support@rescron.com</p>


            </div>

        </div>


    </div>

</div>
