@php
    $totalPnl = $positions['totalPnl'] ?? 0;
    $totalTrades = $positions['total'] ?? 0;
    $currentPage = $positions['currentPage'] ?? 1;
    $recentTrades = $positions['positions'] ?? [];
    $pages = [];
    if (count($recentTrades) > 0) {
        $page_count = ceil($totalTrades / count($recentTrades));
        $page = 1;
        while ($page <= $page_count) {
            array_push($pages, $page);
            $page++;
        }
    }
@endphp


<div class="w-full p-5 mb-5 ts-gray-2 rounded-lg transition-all rescron-card hidden" id="trades">
    <h3 class="capitalize  font-extrabold "><span class="border-b-2">Position History</span>
    </h3>




    <div class="w-full">
        <div class="grid grid-cols-1 gap-3 mt-5">
            @if ($server_url)

                <div class="w-full grid grid-cols-1 md:grid-cols-2 gap-3 mt-5 mb-5">
                    <!-- Total Trades Card -->
                    <div
                        class="w-full flex items-center h-28 ts-gray-2 rounded-lg p-2 border border-slate-800 hover:border-slate-600 transition-all">
                        <div class="w-full">
                            <div class="w-full flex items-center justify-between mb-2">
                                <div class="flex items-center gap-2">
                                    <div class="ts-gray-3 text-purple-500 rounded-full p-2 w-8 h-8">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"
                                            class="w-4 h-4">
                                            <path d="M12 7.5a2.25 2.25 0 100 4.5 2.25 2.25 0 000-4.5z"></path>
                                            <path fill-rule="evenodd"
                                                d="M1.5 4.875C1.5 3.839 2.34 3 3.375 3h17.25c1.035 0 1.875.84 1.875 1.875v9.75c0 1.036-.84 1.875-1.875 1.875H3.375A1.875 1.875 0 011.5 14.625v-9.75zM8.25 9.75a3.75 3.75 0 117.5 0 3.75 3.75 0 01-7.5 0zM18.75 9a.75.75 0 00-.75.75v.008c0 .414.336.75.75.75h.008a.75.75 0 00.75-.75V9.75a.75.75 0 00-.75-.75h-.008zM4.5 9.75A.75.75 0 015.25 9h.008a.75.75 0 01.75.75v.008a.75.75 0 01-.75.75H5.25a.75.75 0 01-.75-.75V9.75z"
                                                clip-rule="evenodd"></path>
                                            <path
                                                d="M2.25 18a.75.75 0 000 1.5c5.4 0 10.63.722 15.6 2.075 1.19.324 2.4-.558 2.4-1.82V18.75a.75.75 0 00-.75-.75H2.25z">
                                            </path>
                                        </svg>
                                    </div>
                                    <p class=" font-bold text-gray-500">Current Balance</p>
                                </div>

                            </div>

                            <div class="w-full flex items-center justify-start gap-5 pl-5">

                                <div class="text-xs font-mono text-gray-500">
                                    Available Balance:
                                </div>
                                <div class="flex items-center space-x-2 font-mono">
                                    {{ number_format($analytics['availableBalance'] ?? 0, 2) }} USDT
                                </div>


                            </div>
                            <div class="w-full flex items-center justify-start gap-5 pl-5">

                                <div class="text-xs font-mono text-gray-500">
                                    Margin Balance:
                                </div>
                                <div class="flex items-center space-x-2 font-mono">
                                    {{ number_format($analytics['marginBalance'] ?? 0, 2) }} USDT
                                </div>


                            </div>
                        </div>

                    </div>
                    <div
                        class="w-full flex items-center h-28 ts-gray-2 rounded-lg p-2 border border-slate-800 hover:border-slate-600 transition-all">
                        <div class="w-full">
                            <div class="w-full flex items-center justify-between mb-2">
                                <div>
                                    <p class=" font-bold text-gray-500">Total Trades</p>
                                </div>
                                <div class="flex items-center space-x-1">


                                </div>
                            </div>

                            <div class="w-full flex items-center justify-between">
                                <div class="flex items-center space-x-2 font-mono">
                                    <div class="ts-gray-3 text-orange-500 rounded-full p-2 w-8 h-8">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="currentColor"
                                            viewBox="0 0 16 16">
                                            <path
                                                d="M6 12.5a.5.5 0 0 1 .5-.5h3a.5.5 0 0 1 0 1h-3a.5.5 0 0 1-.5-.5ZM3 8.062C3 6.76 4.235 5.765 5.53 5.886a26.58 26.58 0 0 0 4.94 0C11.765 5.765 13 6.76 13 8.062v1.157a.933.933 0 0 1-.765.935c-.845.147-2.34.346-4.235.346-1.895 0-3.39-.2-4.235-.346A.933.933 0 0 1 3 9.219V8.062Zm4.542-.827a.25.25 0 0 0-.217.068l-.92.9a24.767 24.767 0 0 1-1.871-.183.25.25 0 0 0-.068.495c.55.076 1.232.149 2.02.193a.25.25 0 0 0 .189-.071l.754-.736.847 1.71a.25.25 0 0 0 .404.062l.932-.97a25.286 25.286 0 0 0 1.922-.188.25.25 0 0 0-.068-.495c-.538.074-1.207.145-1.98.189a.25.25 0 0 0-.166.076l-.754.785-.842-1.7a.25.25 0 0 0-.182-.135Z">
                                            </path>
                                            <path
                                                d="M8.5 1.866a1 1 0 1 0-1 0V3h-2A4.5 4.5 0 0 0 1 7.5V8a1 1 0 0 0-1 1v2a1 1 0 0 0 1 1v1a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-1a1 1 0 0 0 1-1V9a1 1 0 0 0-1-1v-.5A4.5 4.5 0 0 0 10.5 3h-2V1.866ZM14 7.5V13a1 1 0 0 1-1 1H3a1 1 0 0 1-1-1V7.5A3.5 3.5 0 0 1 5.5 4h5A3.5 3.5 0 0 1 14 7.5Z">
                                            </path>
                                        </svg>
                                    </div>

                                    {{ $totalTrades }}
                                </div>
                                <div class="text-xs font-mono text-gray-500">
                                    @if ($totalPnl < 0)
                                        <span class="text-red-500">{{ number_format($totalPnl, 2) }}%</span>
                                    @else
                                        <span class="text-green-500">+{{ number_format($totalPnl, 2) }}%</span>
                                    @endif
                                </div>

                            </div>
                        </div>

                    </div>
                </div>


                {{-- trade  --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6" id="reloadBox">
                    @forelse ($recentTrades as $trade)
                        @php
                            $amount = $trade['amount'] ?? 100;
                            $pnlPercent = $trade['pnlPercent'] ?? 0;
                            $pnl = ($pnlPercent / 100) * $amount;
                        @endphp

                        <div
                            class="ts-gray-2 rounded-lg p-2 border border-slate-800 hover:border-slate-600 transition-all">
                            <div class="flex items-center justify-between mb-2">
                                <div class="flex items-center gap-2">
                                    @if ($trade['callType'] == 'long')
                                        <span class="text-sm px-2 py-1 bg-green-500 text-white rounded-md">B</span>
                                    @else
                                        <span class="text-sm px-2 py-1 bg-red-600 text-white rounded-md">S</span>
                                    @endif

                                    <span class="text-sm font-medium">{{ $trade['symbol'] }}</span>
                                </div>
                                <div class="flex items-center gap-2 text-xs">
                                    <span
                                        class="bg-blue-500 text-white px-2 py-1 rounded capitalize">{{ $trade['margin'] }}</span>
                                    <span class="text-white">{{ $trade['leverage'] }}X</span>
                                    <span
                                        class="@if ($trade['status'] == 'closed') text-gray-500 @else text-orange-500 @endif">{{ ucfirst($trade['status']) }}</span>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 text-sm gap-1">
                                <div>Quantity:</div>
                                @php
                                    $trade_amount = $trade['amount'] ?? 100;
                                    $quantity = number_format(
                                        ($trade_amount / $trade['callPrice']) * $trade['leverage'],
                                        $trade['qPrecision'],
                                    );
                                @endphp

                                <div class="text-right">{{ $quantity }}
                                    {{ str_replace(['USDT', 'USDC'], '', $trade['symbol']) }} </div>

                                <div>Entry Price:</div>
                                <div class="text-right">{{ $trade['callPrice'] }}</div>

                                @if ($trade['status'] == 'open')
                                    <div>Current Price:</div>
                                    <div class="text-right">{{ $trade['currentPrice'] }}</div>

                                    <div>Unrealized PNL:</div>
                                    <div class="text-right {{ $pnl >= 0 ? 'text-green-500' : 'text-red-500' }}">
                                        {{ number_format($pnl, 2) }} USDT
                                    </div>
                                    <div></div>
                                    <div class="text-right {{ $pnlPercent >= 0 ? 'text-green-500' : 'text-red-500' }}">
                                        {{ number_format($pnlPercent, 2) }}%
                                    </div>
                                @else
                                    <div>Closing Price:</div>
                                    <div class="text-right">{{ $trade['closingPrice'] ?? $trade['currentPrice'] }}
                                    </div>

                                    <div>Closing PNL:</div>
                                    <div class="text-right {{ $pnl >= 0 ? 'text-green-500' : 'text-red-500' }}">
                                        {{ number_format($pnl, 2) }} USDT
                                    </div>
                                    <div></div>
                                    <div class="text-right {{ $pnlPercent >= 0 ? 'text-green-500' : 'text-red-500' }}">
                                        {{ number_format($pnlPercent, 2) }}%
                                    </div>
                                @endif




                                <div>Liq. Price:</div>
                                <div class="text-right">
                                    {{ number_format(strtolower($trade['callType']) === 'long' ? $trade['callPrice'] * (1 - 1 / $trade['leverage']) : $trade['callPrice'] * (1 + 1 / $trade['leverage']), $trade['pPrecision']) }}
                                </div>

                                <div>Initial Margin:</div>
                                <div class="text-right">
                                    {{ number_format($trade['amount'] ?? 100, $trade['pPrecision']) }} USDT</div>

                                <div>Margin:</div>
                                <div class="text-right">
                                    @php
                                        $trade_amount = $trade['amount'] ?? 100;
                                    @endphp
                                    {{ number_format($trade_amount * $trade['leverage'], $trade['pPrecision']) }} USDT
                                </div>
                            </div>

                            <div class="flex justify-between text-xs text-gray-400 mt-4">
                                <div>
                                    <div>Opening</div>
                                    <div>{{ date('Y/m/d', $trade['callTime']) }}</div>
                                    <div>{{ date('H:i A', $trade['callTime']) }}</div>
                                </div>

                                <div class="text-right">
                                    <div>Closing</div>
                                    @if ($trade['status'] == 'open')
                                        --
                                    @else
                                        <div>{{ date('Y/m/d', $trade['closingTime'] ?? time()) }}</div>
                                        <div>{{ date('H:i A', $trade['closingTime'] ?? time()) }}</div>
                                    @endif

                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="col-span-full text-center text-gray-400 py-6">
                            @if (is_array($positions))
                                No recent trades available.
                            @else
                                <div class="text-red-500 bg-red-400 p-4 rounded-lg"> {{ $positions }} </div>
                            @endif
                        </div>
                    @endforelse
                </div>

                {{-- pagination --}}
                @if (count($recentTrades) > 0)
                    <div class="flex items-center justify-center space-x-2 my-6">
                        {{-- Previous Arrow --}}
                        <a href="{{ route('admin.binance.index', ['page' => max(1, $currentPage - 1)]) }}"
                            class="flex items-center justify-center w-10 h-10 rounded-full transition-colors duration-200 
                       {{ $currentPage == 1 ? 'opacity-50 cursor-not-allowed bg-[#3a6ea5]/30 text-white' : 'hover:bg-[#3a6ea5] text-white bg-[#3a6ea5]/50' }}">
                            &larr;
                        </a>

                        {{-- Always show first page --}}
                        <a href="{{ route('admin.binance.index', ['page' => 1]) }}"
                            class="flex items-center justify-center w-10 h-10 rounded-full transition-colors duration-200 
                       {{ $currentPage == 1 ? 'bg-[#3a6ea5] text-white font-semibold' : 'text-white bg-[#3a6ea5]/30 hover:bg-[#3a6ea5]' }}">
                            1
                        </a>

                        {{-- Left Ellipsis --}}
                        @if ($currentPage > 4)
                            <span class="text-white px-2">…</span>
                        @endif

                        {{-- Dynamic middle pages --}}
                        @foreach (range(max(2, $currentPage - 1), min(end($pages) - 1, $currentPage + 1)) as $page)
                            @if ($page !== 1 && $page !== end($pages) && $page !== 0)
                                <a href="{{ route('admin.binance.index', ['page' => $page]) }}"
                                    class="flex items-center justify-center w-10 h-10 rounded-full transition-colors duration-200 
                               {{ $page == $currentPage ? 'bg-[#3a6ea5] text-white font-semibold' : 'text-white bg-[#3a6ea5]/30 hover:bg-[#3a6ea5]' }}">
                                    {{ $page }}
                                </a>
                            @endif
                        @endforeach

                        {{-- Right Ellipsis --}}
                        @if ($currentPage < end($pages) - 3)
                            <span class="text-white px-2">…</span>
                        @endif

                        {{-- Always show last page (if > 1) --}}
                        @if (end($pages) > 1)
                            <a href="{{ route('admin.binance.index', ['page' => end($pages)]) }}"
                                class="flex items-center justify-center w-10 h-10 rounded-full transition-colors duration-200 
                           {{ $currentPage == end($pages) ? 'bg-[#3a6ea5] text-white font-semibold' : 'text-white bg-[#3a6ea5]/30 hover:bg-[#3a6ea5]' }}">
                                {{ end($pages) }}
                            </a>
                        @endif

                        {{-- Next Arrow --}}
                        <a href="{{ route('admin.binance.index', ['page' => min(end($pages), $currentPage + 1)]) }}"
                            class="flex items-center justify-center w-10 h-10 rounded-full transition-colors duration-200 
                       {{ $currentPage == end($pages) ? 'opacity-50 cursor-not-allowed bg-[#3a6ea5]/30 text-white' : 'hover:bg-[#3a6ea5] text-white bg-[#3a6ea5]/50' }}">
                            &rarr;
                        </a>
                    </div>
                @endif
            @else
                <div class="relative">
                    <span class="text-xs text-red-500">
                        <span class="material-icons text-xs">warning</span> Error: Binance Plugin is not activated for
                        {{ domain() }}
                    </span>
                </div>

            @endif


        </div>


    </div>

</div>



@push('scripts')
    <script>
        function reloadBoxContent() {
            $('#reloadBox').load(location.href + ' #reloadBox > *');
        }
        setInterval(reloadBoxContent, 5000);
    </script>
@endpush
