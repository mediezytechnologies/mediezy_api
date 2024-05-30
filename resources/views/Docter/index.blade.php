<!DOCTYPE html>
<html>

<head>
    @include('header')
</head>

<body>

    <!-- ======= Header ======= -->

    <header id="header" class="header fixed-top">
        @include('navbar')
    </header>

    <!-- End Header -->
    <!-- ======= Sidebar ======= -->

    <aside id="sidebar" class="sidebar ps-0">
        @include('sidebar')
    </aside>



    <main id="main">





        <div class="wrapper">
            <!--CONTENTS-->
            <div class="container-fluid mainContents">
                <div class="card card-body main_card mt-2 shadow-lg p-3 mb-2">

                    <div class=" row main_heading  mb-2 shadow p-2 subheading">
                        <div id="exportbtns" class="exportbtns col-md-4 col-12">
                            <!-- export buttons -->
                        </div>
                        <div class="col-md-4 col-12">
                            <input type="text" class="form-control text-center" id="SearchBar" placeholder="Search">
                        </div>
                        <div class="col-md-4 col-12 text-end">
                            <a class="btn AddBtn px-4" href="{{url('/Docter/create')}}">+
                                Add</a>

                        </div>
                    </div>

                    <div class="admintoolbar">

                    </div>
                    <div class="table-responsive tablelapView">
                        <table class="table  table-hover MasterTable" id="MasterTable" style="width: 100%;">
                            <thead class=" tablehead">
                                <tr>
                                    <th class="text-center">Sl No</th>
                                    <th class="text-center">Profile Picture</th>
                                    <th class="text-center">Name</th>
                                    <th class="text-center">Email</th>
                                    <th class="text-center">Approve/Reject</th>
                                    <th class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>

                            </tbody>

                        </table>
                    </div>



                </div>
            </div>
        </div>
        <main id="main">

        </main>
        <!-- End Sidebar-->
        @include('footer')
        <script>
            $('.MasterTable thead tr')
                .clone(true)
                .addClass('filters')
                .appendTo('.MasterTable thead');
            var MasterTable = $('.MasterTable').DataTable({
                processing: true,
                orderCellsTop: true,
                fixedHeader: true,
                "dom": 'Blrt<"bottom"ip>',
                "pagingType": 'simple_numbers',
                "language": {
                    "paginate": {
                        "previous": "<i class='bi bi-caret-left-fill'></i>",
                        "next": "<i class='bi bi-caret-right-fill'></i>"
                    }
                },
                buttons: [{
                        extend: 'excelHtml5',
                        exportOptions: {
                            columns: [0, 1, 2]
                        }
                    },
                    {
                        extend: 'pdfHtml5',
                        exportOptions: {
                            columns: [0, 1, 2]
                        }
                    },
                    {
                        extend: 'print',
                        exportOptions: {
                            columns: [0, 1, 2]
                        }
                    },
                ],

                initComplete: function() {
                    $("#MasterTable").wrap("<div style='overflow:auto; width:100%;position:relative;'></div>");
                    var api = this.api();
                    // For each column
                    api
                        .columns()
                        .eq(0)
                        .each(function(colIdx) {
                            // Set the header cell to contain the input element
                            var cell = $('.filters th').eq(
                                $(api.column(colIdx).header()).index()
                            );
                            var title = $(cell).text();
                            if (colIdx < api.columns().nodes().length - 1) {
                                $(cell).html(
                                    '<input type="text" class="text-center searchcolumn" placeholder="Search" />'
                                );
                            } else {
                                $(cell).empty();
                            }
                            // On every keypress in this input
                            $(
                                    'input',
                                    $('.filters th').eq($(api.column(colIdx).header()).index())
                                )
                                .off('keyup change')
                                .on('change', function(e) {
                                    // Get the search value
                                    $(this).attr('title', $(this).val());
                                    var regexr = '({search})'; //$(this).parents('th').find('select').val();

                                    // var cursorPosition = this.selectionStart;
                                    // Search the column for that value
                                    api
                                        .column(colIdx)
                                        .search(
                                            this.value != '' ?
                                            regexr.replace('{search}', '(((' + this.value + ')))') :
                                            '',
                                            this.value != '',
                                            this.value == ''
                                        )
                                        .draw();
                                })
                                .on('keyup', function(e) {
                                    e.stopPropagation();

                                    $(this).trigger('change');
                                    $(this)
                                        .focus()[0]
                                    // .setSelectionRange(cursorPosition, cursorPosition);
                                });
                        });
                    $('.dt-buttons').appendTo('#exportbtns');
                },


                ajax: "{{ route('Docter.index') }}",
columns: [
    {
        data: 'DT_RowIndex',
        name: 'DT_RowIndex'
    },
    {
        data: 'docter_image',
        name: 'docter_image',
        render: function (data, type, full, meta) {
            // Assuming 'docter_image' contains the image file name
            var imageUrl = '/DocterImages/images/' + data;
            // Use CSS to style the image as round
            return '<div class="rounded-image"><img src="' + imageUrl + '" alt="Doctor Image" width="40px" height="40px"></div>';
        }
    },
    {
        data: 'firstname',
        name: 'firstname'
    },
    {
        data: 'email',
        name: 'email'
    },
    {
        data: 'approve_status',
        name: 'approve_status',
        orderable: false,
        searchable: false
    },
    {
        data: 'action',
        name: 'action',
        orderable: false,
        searchable: false
    },
]

            });



            //edit Specialization
          



            //Update Enquiry Type
            $("#update_enquiry").validate({
                rules: {
                    UpdateEnquiryName: {
                        required: true,
                        minlength: 2,
                        maxlength: 15,
                    }
                },
                messages: {
                    UpdateEnquiryName: {
                        required: "This field is required",
                        minlength: "atleast 2 characters",
                        maxlength: "maximum 15 characters",
                    }
                },
                submitHandler: function(form) {
                    var UpdateId = $('#update_enquiry_id').val();
                    var UpdateSpecialization = $('#update_enquiry_name').val();
                    var UpdateRemark = $('#update_remarks').val();
                    var UpdatedDy = $('#updated_by').val();


                    $.ajax({

                        url: "/api/specialization/" + UpdateId + "",
                        method: "PUT",
                        timeout: 0,
                        headers: {
                            "Accept": "application/json",
                            "Content-Type": "application/x-www-form-urlencoded"
                        },
                        data: {
                            specialization: UpdateSpecialization,
                            remark: UpdateRemark,


                        },
                        beforeSend: function() {
                            $('.loader').show();
                            $('#EnquiryModal').modal('hide');
                            $('.mainContents').hide();
                            $('#ResponseImage').html("");
                            $('#ResponseText').text("");
                        },
                    }).done(function(response) {
                        $('.mainContents').show();
                        $('.loader').hide();
                        MasterTable.ajax.reload();
                        console.log(response);
                        console.log(response.message);
                        var SpecializtionResult = JSON.stringify(response);
                        console.log(SpecializtionResult);
                        var SpecializtionObj = JSON.parse(SpecializtionResult);
                        if (SpecializtionObj.success == true) {
                            if (SpecializtionObj.code == "0") {

                                swal("Warning", response.message, "warning");
                            } else if (SpecializtionObj.code == "1") {

                                swal("Success", response.message, "success");
                            } else if (SpecializtionObj.code == "2") {

                                swal("Error", response.message, "error");
                            }
                        } else {
                            // Error icon
                            swal("Some Error Occurred!!!", "Please Try Again", "error");
                        }
                    });
                }
            });
            $('#MasterTable').on('click', '.btn_delete', function() {

                        var DeleteSpecialization = $(this).val();
                        swal({
                            title: "Are you sure?",
                            text: "Once deleted, you will not be able to recover this.",
                            icon: "warning",
                            buttons: true,
                            dangerMode: true,
                        }).then((willDelete) => {
                            if (willDelete) {
                                $.ajax({
                                    url: "/api/specialization/" + DeleteSpecialization,
                                    method: "DELETE",
                                    timeout: 0,
                                    headers: {
                                        "Accept": "application/json",
                                        "Content-Type": "application/x-www-form-urlencoded"
                                    },
                                    beforeSend: function() {
                                        $('.loader').show();
                                        $('.mainContents').hide();
                                    },
                                }).done(function(response) {
                                    $('.mainContents').show();
                                    $('.loader').hide();
                                    MasterTable.ajax.reload();
                                    console.log(response);
                                    console.log(response.message);
                                    var SpecializtionResult = JSON.stringify(response);
                                    console.log(SpecializtionResult);
                                    var SpecializtionObj = JSON.parse(SpecializtionResult);
                                    if (SpecializtionObj.success == true) {
                                        if (SpecializtionObj.code == "0") {

                                            swal("Warning", response.message, "warning");
                                        } else if (SpecializtionObj.code == "1") {

                                            swal("Success", response.message, "success");
                                        } else if (SpecializtionObj.code == "2") {

                                            swal("Error", response.message, "error");
                                        }
                                    } else {
                                        // Error icon
                                        swal("Some Error Occurred!!!", "Please Try Again", "error");
                                    }
                                });
                            }
                        })
                     });


                        //View Enquiry Type
                        $('#MasterTable').on('click', '.btn_view', function() {
                            var ViewEnType = $(this).val();
                            console.log(ViewEnType);
                            var settings = {
                                "url": "/api/enquiry-type/" + ViewEnType + "",
                                "method": "GET",
                                "timeout": 0,
                            };

                            $.ajax(settings).done(function(response) {
                                console.log(response);
                                var EnResult = JSON.stringify(response);
                                console.log(EnResult);
                                var Enedit = JSON.parse(EnResult);
                                if (Enedit.success == true) {
                                    $('#enquiryViewModal').modal('show');
                                    $('#view_enquiry').show();
                                    var EnDataArray = Enedit.enquirytype;
                                    for (const key in EnDataArray) {
                                        var EnquiryName = EnDataArray['name'];
                                        var EnquiryRemark = EnDataArray['remarks'];
                                        var EnquiryId = EnDataArray['id'];

                                    }
                                    $("#view_enquiry_id").val(EnquiryId);
                                    $("#view_enquiry_name").val(EnquiryName);
                                    $("#view_remarks").val(EnquiryRemark);
                                }
                            });

                        });



                        function approveOrRejectDoctor(doctorId, action) {
        $.ajax({
            url: '/api/approveorreject',
            method: 'POST',
            data: {
                doctor_id: doctorId,
                action: action
            },
            success: function(response) {
                console.log(response.message);
                toastr.success( response.message);
            },
            error: function(error) {
                console.error('Error:', error.responseJSON.message);
                // Handle the error or show a notification to the user
            }
        });
    }



        $('#MasterTable').on('click', '.approve-button', function() {
    var doctorId = $(this).data('doctor-id');
    console.log(doctorId);
    approveOrRejectDoctor(doctorId, 'approve');
});

$('.reject-button').click(function() {
    var doctorId = $(this).data('doctor-id');
    console.log(doctorId);
    approveOrRejectDoctor(doctorId, 'reject');
});

        </script>
</body>

</html>
