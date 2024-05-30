<?php $__env->startSection('content'); ?>
<div class="content-wrapper" style="padding-left: 100px; padding-right: 20px;">
    <!-- Content Header (Page header) -->
    <section class="content-header">
        <h1>Manage Doctor-Clinic Specializations</h1>
    </section>

    <!-- Main content -->
    <section class="content">
        <!-- Form to assign clinics to specializations -->
        <div class="box">
            <div class="box-header with-border">
                <h6 class="box-title">Assign Clinics to Specializations</h6>
            </div>
            <!-- /.box-header -->
            <div class="box-body">
                <form action="<?php echo e(route('saveDoctorClinicSpecializations')); ?>" method="post">
                    <?php echo csrf_field(); ?>

                    <div class="form-group">
                        <label for="special_id">Select Specializations:</label>
                        <select name="special_id" id="special_id" class="form-control" required>
                            <?php $__currentLoopData = $specilization_data; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $specialization): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($specialization->id); ?>"><?php echo e($specialization->specialization); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                    </div>


                    <div class="form-group">
                        <label for="clinic_ids">Select Clinics:</label>
                        <select name="clinic_ids[]" id="clinic_ids" class="form-control" multiple required>
                            <?php $__currentLoopData = $clinic_data; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $clinic): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($clinic->clinic_id); ?>"><?php echo e($clinic->clinic_name); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                    </div>

                    <button type="submit" class="btn btn-primary btn-sm mt-3 mb-3">Assign Clinic to Specializations</button>
                </form>
            </div>
            <!-- /.box-body -->
        </div>
        <!-- /.box -->

        <!-- Form to assign doctors to specializations -->
        <div class="box">
            <div class="box-header with-border">
                <h6 class="box-title">Assign Doctors to Specializations</h6>
            </div>
            <!-- /.box-header -->
            <div class="box-body">
                <form action="<?php echo e(route('saveClinicDoctorSpecializations')); ?>" method="post">
                    <?php echo csrf_field(); ?>

                    <div class="form-group">
                        <label for="specialization_id_doctor">Select Specializations:</label>
                        <select name="specialization_id_doctor" id="specialization_id_doctor" class="form-control" required>
                            <?php $__currentLoopData = $specilization_data; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $specialization): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($specialization->id); ?>"><?php echo e($specialization->specialization); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="doctor_ids">Select Doctors:</label>
                        <select name="doctor_ids[]" id="doctor_ids" class="form-control" multiple>
                            <?php $__currentLoopData = $doctor_data; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $doctor): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($doctor->id); ?>"><?php echo e($doctor->firstname); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                    </div>

                    <button type="submit" class="btn btn-primary btn-sm mt-3 mb-3">Assign Doctor to Specializations</button>
                </form>
            </div>
            <!-- /.box-body -->
        </div>
        <!-- /.box -->
    </section>
    <!-- /.content -->
</div>
<!-- /.content-wrapper -->
<?php $__env->stopSection(); ?>

<?php echo $__env->make('backend.layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /home/1163996.cloudwaysapps.com/tdtjxymxyy/public_html/resources/views/backend/relation_management/doctor_clinic_specialization.blade.php ENDPATH**/ ?>