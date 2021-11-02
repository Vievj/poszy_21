@extends('layouts.app')

@section('title', __('sale.pos_sale'))

@section('content')
    <section class="content no-print">
        <input type="hidden" id="amount_rounding_method" value="{{$pos_settings['amount_rounding_method'] ?? ''}}">
        @if(!empty($pos_settings['allow_overselling']))
            <input type="hidden" id="is_overselling_allowed">
        @endif
        @if(session('business.enable_rp') == 1)
            <input type="hidden" id="reward_point_enabled">
        @endif
        @php
            $is_discount_enabled = $pos_settings['disable_discount'] != 1 ? true : false;
            $is_rp_enabled = session('business.enable_rp') == 1 ? true : false;
        @endphp
        {!! Form::open(['url' => action('SellPosController@store'), 'method' => 'post', 'id' => 'add_pos_sell_form' ]) !!}
        <div class="row mb-12">
            <div class="col-md-12">
                <div class="row">
                    <div class="@if(empty($pos_settings['hide_product_suggestion'])) col-md-7 @else col-md-10 col-md-offset-1 @endif no-padding pr-12">
                        <div class="box box-solid mb-12">
                            <div class="box-body pb-0">
                            {!! Form::hidden('location_id', $default_location->id ?? null , ['id' => 'location_id', 'data-receipt_printer_type' => !empty($default_location->receipt_printer_type) ? $default_location->receipt_printer_type : 'browser', 'data-default_payment_accounts' => $default_location->default_payment_accounts ?? '']); !!}
                            <!-- sub_type -->
                                {!! Form::hidden('sub_type', isset($sub_type) ? $sub_type : null) !!}
                                <input type="hidden" id="item_addition_method"
                                       value="{{$business_details->item_addition_method}}">
                                @include('sale_pos.partials.pos_form')

                                @include('sale_pos.partials.pos_form_totals')

                                @include('sale_pos.partials.payment_modal')

                                @if(empty($pos_settings['disable_suspend']))
                                    @include('sale_pos.partials.suspend_note_modal')
                                @endif

                                @if(empty($pos_settings['disable_recurring_invoice']))
                                    @include('sale_pos.partials.recurring_invoice_modal')
                                @endif
                            </div>
                        </div>
                    </div>
                    @if(empty($pos_settings['hide_product_suggestion']) && !isMobile())
                        <div class="col-md-5 no-padding">
                            @include('sale_pos.partials.pos_sidebar')
                        </div>
                    @endif
                </div>
            </div>
        </div>
        @include('sale_pos.partials.pos_form_actions')
        {!! Form::close() !!}
    </section>

    <!-- This will be printed -->
    <section class="invoice print_section" id="receipt_section"></section>
    <div class="modal fade contact_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel">
        @include('contact.create', ['quick_add' => true])
    </div>
    @if(empty($pos_settings['hide_product_suggestion']) && isMobile())
        @include('sale_pos.partials.mobile_product_suggestions')
    @endif
    <!-- /.content -->
    <div class="modal fade register_details_modal" tabindex="-1" role="dialog"
         aria-labelledby="gridSystemModalLabel"></div>
    <div class="modal fade close_register_modal" tabindex="-1" role="dialog"
         aria-labelledby="gridSystemModalLabel"></div>
    <!-- quick product modal -->
    <div class="modal fade quick_add_product_modal" tabindex="-1" role="dialog" aria-labelledby="modalTitle"></div>

    <div class="modal fade" id="expense_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel"></div>

    @include('sale_pos.partials.configure_search_modal')

    @include('sale_pos.partials.recent_transactions_modal')

    @include('sale_pos.partials.weighing_scale_modal')

@stop
@section('css')
    <!-- include module css -->
    @if(!empty($pos_module_data))
        @foreach($pos_module_data as $key => $value)
            @if(!empty($value['module_css_path']))
                @includeIf($value['module_css_path'])
            @endif
        @endforeach
    @endif
@stop


@section('javascript')
    <div class="modal fade" id="exampleModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
         aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">My Weight Machine</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class='form-group'>
                        <input type='text' class='form-control' value='' id='weight_machine' />
                        <input type='hidden' value='' id='weight_machine_i' />
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="button" onclick='storeValue()' class="btn btn-primary">Apply</button>
                </div>
            </div>
        </div>
    </div>
    <script>
        const checker = {};

        const connectSerial = async function () {

            /*
            usbProductId: 29987
                usbVendorId: 6790
            */

            if ("serial" in navigator) {
                console.log('checker.port', checker.port)
                if (checker.port) {
                    const ports = await navigator.serial.getPorts();


                    if (ports.length > 1) {
                        alert('Many Ports Paired : Reset your browser permission and just select one serial port')
                        return;
                    }
                    console.log('connected ports true', ports)
                    checker.port = ports[0];
                } else {
                    checker.port = await navigator.serial.requestPort({});

                    const ports = await navigator.serial.getPorts();
                    console.log('connected ports', ports)
                    checker.port = await navigator.serial.requestPort();

                    console.log('checker.port get info', checker.port.getInfo())

                }
                console.log('checker.port get info', checker.port.getInfo())

                await checker.port.open({baudRate: 9600, dataBits: 8});
                const reader = checker.port.readable.getReader();

                serialReader2(reader)

                return;
            }
            alert('serial port is not support for this browser')
        }


        const serialReader2 = async function (reader) {

            while (true) {
                const {value, done} = await reader.read();
                if (done) {
                    console.log('[read] DONE', done);
                    // Allow the serial port to be closed later.
                    // reader.releaseLock();
                    // break;
                }
                if (value) {
                    // console.log('value x', value)

                    const decoder = new TextDecoder("utf-8");
                    if (decoder.decode(value).length >= 6) {
                        console.log('current value string 6 chars', decoder.decode(value));
                    }
                    if (decoder.decode(value).length >= 5) {
                        $('#weight_machine').val('')
                        $('#weight_machine').val(decoder.decode(value))
                        console.log('current value string 5 chars', decoder.decode(value));
                    }
                }

            }
        }


        function showMachine(input) {
            checker.input_val = input;
            let weight_machine = $('#weight_machine');
            usbConnect();
            $('#exampleModal').modal('show');
            weight_machine.val('');
        }
    </script>

    <script src="{{ asset('js/pos.js?v=' . $asset_v) }}"></script>
    <script src="{{ asset('js/printer.js?v=' . $asset_v) }}"></script>
    <script src="{{ asset('js/product.js?v=' . $asset_v) }}"></script>
    <script src="{{ asset('js/opening_stock.js?v=' . $asset_v) }}"></script>


    @include('sale_pos.partials.keyboard_shortcuts')

    <!-- Call restaurant module if defined -->
    @if(in_array('tables' ,$enabled_modules) || in_array('modifiers' ,$enabled_modules) || in_array('service_staff' ,$enabled_modules))
        <script src="{{ asset('js/restaurant.js?v=' . $asset_v) }}"></script>
    @endif
    <!-- include module js -->
    @if(!empty($pos_module_data))
        @foreach($pos_module_data as $key => $value)
            @if(!empty($value['module_js_path']))
                @includeIf($value['module_js_path'], ['view_data' => $value['view_data']])
            @endif
        @endforeach
    @endif
@endsection