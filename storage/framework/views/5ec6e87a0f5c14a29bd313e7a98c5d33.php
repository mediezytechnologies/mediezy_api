<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mediezy Admin</title>
    <!-- Include Tailwind CSS -->
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>

<body class="bg-gray-50 dark:bg-gray-900">
    <section class="flex items-center justify-center h-screen px-6 py-8 mx-auto">
        <div class="w-full bg-white rounded-lg shadow md:max-w-md dark:bg-gray-800 dark:border-gray-700">
            <div class="p-6 space-y-4 md:space-y-6 sm:p-8">
                <img src="<?php echo e(asset('admin/img/mediezy_logo_green.png')); ?>" alt="Mediezy Logo"
                    class="w-full max-w-xs mx-auto mb-4" style="max-width: 200px;">

                <h8 class="text-xl font-medium leading-tight tracking-tight text-gray-900 md:text-2xl dark:text-white">
                    Welcome Administrator
                </h8>
                <?php if($errors->any()): ?>
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative"
                        role="alert">
                        <ul>
                            <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <li><?php echo e($error); ?></li>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </ul>
                    </div>
                <?php endif; ?>
                <form method="POST" action="<?php echo e(route('login')); ?>" class="space-y-4 md:space-y-6">
                    <?php echo csrf_field(); ?> <!-- CSRF Token -->
                    <div>
                        <label for="email" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Your
                            email</label>
                        <input type="email" name="email" id="email"
                            class="w-full px-4 py-2.5 text-sm bg-gray-50 border border-gray-300 rounded-lg focus:ring-primary-600 focus:border-primary-600 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                            placeholder="name@mediezy.com" required="">
                    </div>
                    <div>
                        <label for="password"
                            class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Password</label>
                        <input type="password" name="password" id="password" placeholder="••••••••"
                            class="w-full px-4 py-2.5 text-sm bg-gray-50 border border-gray-300 rounded-lg focus:ring-primary-600 focus:border-primary-600 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                            required="">
                    </div>
                    <div class="flex items-center justify-between">
                        <div class="flex items-start">
                            <input id="remember" aria-describedby="remember" type="checkbox"
                                class="w-4 h-4 border border-gray-300 rounded bg-gray-50 focus:ring-3 focus:ring-primary-300 dark:bg-gray-700 dark:border-gray-600 dark:focus:ring-primary-600 dark:ring-offset-gray-800">
                            <label for="remember" class="ml-2 text-sm text-gray-500 dark:text-gray-300">Remember
                                me</label>
                        </div>
                        <a href="#"
                            class="text-sm font-medium text-primary-600 hover:underline dark:text-primary-500">Forgot
                            password?</a>
                    </div>
                    <button type="submit"
                        class="w-full px-4 py-2.5 text-sm font-medium text-white bg-black rounded-lg focus:ring-4 focus:outline-none focus:ring-primary-300 hover:bg-primary-700 dark:bg-primary-600 dark:hover:bg-primary-700 dark:focus:ring-primary-800"
                        style="background-color: rgb(56,212,172)">Log in</button>
                </form>
            </div>
        </div>
    </section>
</body>

</html>
<?php /**PATH /home/1163996.cloudwaysapps.com/tdtjxymxyy/public_html/resources/views/backend/admin_login/admin_login_page.blade.php ENDPATH**/ ?>