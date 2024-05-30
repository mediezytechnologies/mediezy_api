<?php $__env->startSection('content'); ?>

<div class="content-wrapper" style="padding-left: 100px; padding-right: 20px;">
    <h2 class="text-bold mt-4">Manage Doctor-Clinic Relations</h2>

    <form action="<?php echo e(route('saveDoctorClinicRelations')); ?>" method="post" class="mb-4">
        <?php echo csrf_field(); ?>

        <!-- Assign doctors to clinics -->
        <label for="doctor_id">Select Doctor:</label>
        <select class="form-control" name="doctor_id" id="doctor_id">
            <?php $__currentLoopData = $doctor_data; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $doctor): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <option value="<?php echo e($doctor->id); ?>"><?php echo e($doctor->firstname); ?></option>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </select>

        <label for="clinic_ids">Select Clinics:</label>
        <select class="form-control" name="clinic_ids[]" id="clinic_ids" multiple>
            <?php $__currentLoopData = $clinic_data; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $clinic): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <option value="<?php echo e($clinic->clinic_id); ?>"><?php echo e($clinic->clinic_name); ?></option>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </select>

        <button type="submit" class="btn btn-primary mt-3">Assign Doctor to Clinic</button>
    </form>

    <!-- Assign clinics to doctors -->
    <form action="<?php echo e(route('saveClinicDoctorRelations')); ?>" method="post" class="mb-4">
        <?php echo csrf_field(); ?>

        <label for="clinic_id">Select Clinic:</label>
        <select class="form-control" name="clinic_id" id="clinic_id">
            <?php $__currentLoopData = $clinic_data; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $clinic): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <option value="<?php echo e($clinic->clinic_id); ?>"><?php echo e($clinic->clinic_name); ?></option>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </select>

        <label for="doctor_ids">Select Doctors:</label>
        <select class="form-control" name="doctor_ids[]" id="doctor_ids" multiple>
            <?php $__currentLoopData = $doctor_data; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $doctor): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <option value="<?php echo e($doctor->id); ?>"><?php echo e($doctor->firstname); ?></option>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </select>

        <button type="submit" class="btn btn-primary mt-3">Assign Clinic to Doctor</button>
    </form>
</div>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('backend.layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /home/1163996.cloudwaysapps.com/tdtjxymxyy/public_html/resources/views/backend/relation_management/manage_doctor_clinic.blade.php ENDPATH**/ ?>