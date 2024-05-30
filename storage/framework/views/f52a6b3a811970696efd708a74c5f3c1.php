<style>

</style>

<?php $__env->startSection('content'); ?>
<div class="container">
    <h2>Clinic-Wise Doctor Consultation Fee</h2>
    
    <?php if(session('success')): ?>
        <div class="alert alert-success">
            <?php echo e(session('success')); ?>

        </div>
    <?php endif; ?>

    <form method="POST" action="<?php echo e(route('clinic-consultations.clinicwiseConsultationFees')); ?>">
        <?php echo csrf_field(); ?>
        
        <label for="doctor_id">Select Doctor:</label>
        <select class="form-control" name="doctor_id" id="doctor_id" >
            <?php $__currentLoopData = $doctors; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $doctor): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <option value="<?php echo e($doctor->id); ?>"><?php echo e($doctor->firstname); ?></option>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </select>

        <label for="clinic_id">Select Clinics:</label>
        <select class="form-control" name="clinic_id" id="clinic_id" >
            <?php $__currentLoopData = $clinics; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $clinic): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <option value="<?php echo e($clinic->clinic_id); ?>"><?php echo e($clinic->clinic_name); ?></option>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </select>

        <div class="mb-3">
            <label for="consultation_fee" class="form-label">Consultation Fee</label>
            <input type="number" class="form-control" id="consultation_fee" name="consultation_fee" required>
        </div>
        <button type="submit" class="btn btn-primary">Submit</button>
    </form>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('backend.layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /home/1163996.cloudwaysapps.com/tdtjxymxyy/public_html/resources/views/clinic-consultation/create.blade.php ENDPATH**/ ?>