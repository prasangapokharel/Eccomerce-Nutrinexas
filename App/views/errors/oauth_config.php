<?php
$title = 'OAuth Configuration Required';
include APPROOT . '/views/layouts/header.php';
?>

<div class="min-h-screen bg-gray-50 flex flex-col justify-center py-12 sm:px-6 lg:px-8">
    <div class="sm:mx-auto sm:w-full sm:max-w-md">
        <div class="text-center">
            <svg class="mx-auto h-12 w-12 text-yellow-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z" />
            </svg>
            <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
                OAuth Configuration Required
            </h2>
            <p class="mt-2 text-center text-sm text-gray-600">
                Google OAuth credentials need to be configured
            </p>
        </div>
    </div>

    <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-2xl">
        <div class="bg-white py-8 px-4 shadow sm:rounded-lg sm:px-10">
            <div class="space-y-6">
                <div class="bg-yellow-50 border border-yellow-200 rounded-md p-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-yellow-800">
                                Configuration Issue
                            </h3>
                            <div class="mt-2 text-sm text-yellow-700">
                                <p>Google OAuth is not properly configured. Please follow the steps below to set it up.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-blue-50 border border-blue-200 rounded-md p-4">
                    <h3 class="text-lg font-medium text-blue-900 mb-4">Setup Instructions</h3>
                    <ol class="list-decimal list-inside space-y-3 text-sm text-blue-800">
                        <li>
                            <strong>Go to Google Cloud Console:</strong>
                            <br>Visit <a href="https://console.cloud.google.com/" target="_blank" class="text-blue-600 underline">https://console.cloud.google.com/</a>
                        </li>
                        <li>
                            <strong>Create or Select Project:</strong>
                            <br>Create a new project or select an existing one
                        </li>
                        <li>
                            <strong>Enable Google+ API:</strong>
                            <br>Go to "APIs & Services" > "Library" and enable "Google+ API"
                        </li>
                        <li>
                            <strong>Create OAuth Credentials:</strong>
                            <br>• Go to "APIs & Services" > "Credentials"
                            <br>• Click "Create Credentials" > "OAuth 2.0 Client IDs"
                            <br>• Choose "Web application"
                            <br>• Set <strong>Authorized redirect URI</strong> to:
                            <br><code class="bg-gray-100 px-2 py-1 rounded text-xs">http://localhost:8000/oauth/callback?provider=google</code>
                        </li>
                        <li>
                            <strong>Update Environment File:</strong>
                            <br>Replace the placeholder values in <code class="bg-gray-100 px-1 rounded">.env.development</code>:
                            <br><code class="bg-gray-100 px-2 py-1 rounded text-xs block mt-1">
                                GOOGLE_CLIENT_ID=your_actual_client_id<br>
                                GOOGLE_CLIENT_SECRET=your_actual_client_secret
                            </code>
                        </li>
                    </ol>
                </div>

                <div class="bg-green-50 border border-green-200 rounded-md p-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-green-800">
                                After Configuration
                            </h3>
                            <div class="mt-2 text-sm text-green-700">
                                <p>Once you've updated the credentials, restart your server and try the OAuth login again.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex justify-center space-x-4">
                    <a href="<?= \App\Core\View::url('auth/login') ?>" 
                       class="bg-gray-600 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                        Back to Login
                    </a>
                    <a href="<?= \App\Core\View::url('home') ?>" 
                       class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                        Go to Home
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include APPROOT . '/views/layouts/footer.php'; ?>