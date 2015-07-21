@extends('accounts.nav')

@section('head')
	@parent

		<script src="{{ asset('js/pdf_viewer.js') }}" type="text/javascript"></script>
    	<script src="{{ asset('js/compatibility.js') }}" type="text/javascript"></script>

        <link href="{{ asset('css/jsoneditor.min.css') }}" rel="stylesheet" type="text/css">
        <script src="{{ asset('js/jsoneditor.min.js') }}" type="text/javascript"></script>

        <script src="{{ asset('js/pdfmake.min.js') }}" type="text/javascript"></script>
        <script src="{{ asset('js/vfs_fonts.js') }}" type="text/javascript"></script>

      <style type="text/css">

        select.form-control {
            background: #FFFFFF !important;        
            margin-right: 12px;
        }
        table {
            background: #FFFFFF !important;        
        }

      </style>

@stop

@section('content')	
	@parent
	@include('accounts.nav_advanced')



  <script>
    var invoiceDesigns = {!! $invoiceDesigns !!};
    var invoice = {!! json_encode($invoice) !!};      
    var sections = ['content', 'styles', 'defaultStyle', 'pageMargins', 'header', 'footer'];
    var customDesign = origCustomDesign = {!! $customDesign !!};

    function getPDFString(cb, force) {
      invoice.is_pro = {!! Auth::user()->isPro() ? 'true' : 'false' !!};
      invoice.account.hide_quantity = {!! Auth::user()->account->hide_quantity ? 'true' : 'false' !!};
      invoice.account.hide_paid_to_date = {!! Auth::user()->account->hide_paid_to_date ? 'true' : 'false' !!};
      invoice.invoice_design_id = {!! Auth::user()->account->invoice_design_id !!};

      NINJA.primaryColor = '{!! Auth::user()->account->primary_color !!}';
      NINJA.secondaryColor = '{!! Auth::user()->account->secondary_color !!}';
      NINJA.fontSize = {!! Auth::user()->account->font_size !!};

      generatePDF(invoice, getDesignJavascript(), force, cb);
    }

    function getDesignJavascript() {
      var id = $('#invoice_design_id').val();
      if (id == '-1') {
        showMoreDesigns(); 
        $('#invoice_design_id').val(1);
        return invoiceDesigns[0].javascript;        
      } else {
        return JSON.stringify(customDesign);
      }
    }

    function loadEditor(section)
    {
        editorSection = section;
        editor.set(customDesign[section]);
        editor.expandAll();        
    }    

    function saveEditor(data)
    {        
        setTimeout(function() {
            customDesign[editorSection] = editor.get();           
            refreshPDF();        
        }, 100)                
    }

    function onSelectChange()
    {
        var id = $('#invoice_design_id').val();
        
        if (parseInt(id)) {
            customDesign = JSON.parse(invoiceDesigns[id-1].javascript);
        } else {
            customDesign = origCustomDesign;
        }

        loadEditor(editorSection);
        refreshPDF(true);
    }

    function submitForm()
    {
        $('#custom_design').val(JSON.stringify(customDesign));
        $('form.warn-on-exit').submit();
    }

    $(function() {                       
       refreshPDF(true);
      
        var container = document.getElementById("jsoneditor");
          var options = {
            mode: 'form',
            modes: ['form', 'code'],
            error: function (err) {
              console.error(err.toString());
            },
            change: function() {
              saveEditor();
            }
          };
        window.editor = new JSONEditor(container, options);      
        loadEditor('content');

        $('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
          var target = $(e.target).attr("href") // activated tab
          target = target.substring(1); // strip leading #
          loadEditor(target);
        });        
    });

  </script> 


  <div class="row">
    <div class="col-md-6">

      {!! Former::open()->addClass('warn-on-exit')->onchange('refreshPDF()') !!}      
      {!! Former::populateField('invoice_design_id', $account->invoice_design_id) !!}

        <div style="display:none">
            {!! Former::text('custom_design') !!}
        </div>


      <div role="tabpanel">
        <ul class="nav nav-tabs" role="tablist" style="border: none">
            <li role="presentation" class="active"><a href="#content" aria-controls="content" role="tab" data-toggle="tab">{{ trans('texts.content') }}</a></li>
            <li role="presentation"><a href="#styles" aria-controls="styles" role="tab" data-toggle="tab">{{ trans('texts.styles') }}</a></li>
            <li role="presentation"><a href="#defaultStyle" aria-controls="defaultStyle" role="tab" data-toggle="tab">{{ trans('texts.defaults') }}</a></li>
            <li role="presentation"><a href="#pageMargins" aria-controls="margins" role="tab" data-toggle="tab">{{ trans('texts.margins') }}</a></li>
            <li role="presentation"><a href="#header" aria-controls="header" role="tab" data-toggle="tab">{{ trans('texts.header') }}</a></li>
            <li role="presentation"><a href="#footer" aria-controls="footer" role="tab" data-toggle="tab">{{ trans('texts.footer') }}</a></li>
        </ul>
    </div>
    <div id="jsoneditor" style="width: 550px; height: 743px;"></div>
        
    <p>&nbsp;</p>

      {!! Former::actions( 
            Former::select('invoice_design_id')->style('display:inline;width:120px')->fromQuery($invoiceDesigns, 'name', 'id')->onchange('onSelectChange()')->raw(),
            Button::success(trans('texts.save'))->withAttributes(['onclick' => 'submitForm()'])->large()->appendIcon(Icon::create('floppy-disk'))
        ) !!}

      @if (!Auth::user()->isPro())
      <script>
          $(function() {   
            $('form.warn-on-exit input').prop('disabled', true);
          });
      </script> 
      @endif

      {!! Former::close() !!}

    </div>
    <div class="col-md-6">

      @include('invoices.pdf', ['account' => Auth::user()->account, 'pdfHeight' => 800])

    </div>
  </div>

@stop