@extends('layouts.app')

@section('title', 'Admin Dashboard')

@section('nav-links')
    <a href="{{ route('admin.dashboard') }}" class="inline-flex items-center border-b-2 border-primary-500 px-1 pt-1 text-sm font-medium text-gray-900">
        Dashboard
    </a>
    <a href="#" class="inline-flex items-center border-b-2 border-transparent px-1 pt-1 text-sm font-medium text-gray-500 hover:border-gray-300 hover:text-gray-700">
        Users
    </a>
    <a href="#" class="inline-flex items-center border-b-2 border-transparent px-1 pt-1 text-sm font-medium text-gray-500 hover:border-gray-300 hover:text-gray-700">
        Settings
    </a>
@endsection

@section('header')
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-gray-900">Super Admin Dashboard</h1>
        <div class="text-sm text-gray-500">
            Last login: {{ Auth::user()->last_login_at ? Auth::user()->last_login_at->diffForHumans() : 'First time' }}
        </div>
    </div>
@endsection

@section('content')
    <!-- Stats Overview -->
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4 mb-8">
        <!-- Total Users -->
        <div class="overflow-hidden rounded-lg bg-white px-4 py-5 shadow border border-gray-200 sm:p-6">
            <dt class="truncate text-sm font-medium text-gray-500">Total Users</dt>
            <dd class="mt-1 text-3xl font-semibold tracking-tight text-gray-900">{{ \App\Models\User::count() }}</dd>
        </div>

        <!-- Active Users -->
        <div class="overflow-hidden rounded-lg bg-white px-4 py-5 shadow border border-gray-200 sm:p-6">
            <dt class="truncate text-sm font-medium text-gray-500">Active Users</dt>
            <dd class="mt-1 text-3xl font-semibold tracking-tight text-green-600">{{ \App\Models\User::active()->count() }}</dd>
        </div>

        <!-- Pending Approvals -->
        <div class="overflow-hidden rounded-lg bg-white px-4 py-5 shadow border border-gray-200 sm:p-6">
            <dt class="truncate text-sm font-medium text-gray-500">Pending Approvals</dt>
            <dd class="mt-1 text-3xl font-semibold tracking-tight text-yellow-600">0</dd>
        </div>

        <!-- Total Securities -->
        <div class="overflow-hidden rounded-lg bg-white px-4 py-5 shadow border border-gray-200 sm:p-6">
            <dt class="truncate text-sm font-medium text-gray-500">Total Securities</dt>
            <dd class="mt-1 text-3xl font-semibold tracking-tight text-primary-600">0</dd>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="mb-8">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Quick Actions</h2>
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <a href="#" class="relative block rounded-lg border-2 border-dashed border-gray-300 p-6 text-center hover:border-primary-400 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 transition-colors duration-200">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                <span class="mt-2 block text-sm font-medium text-gray-900">Create User</span>
            </a>

            <a href="#" class="relative block rounded-lg border-2 border-dashed border-gray-300 p-6 text-center hover:border-primary-400 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 transition-colors duration-200">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <span class="mt-2 block text-sm font-medium text-gray-900">View Reports</span>
            </a>

            <a href="#" class="relative block rounded-lg border-2 border-dashed border-gray-300 p-6 text-center hover:border-primary-400 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 transition-colors duration-200">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                <span class="mt-2 block text-sm font-medium text-gray-900">System Settings</span>
            </a>

            <a href="#" class="relative block rounded-lg border-2 border-dashed border-gray-300 p-6 text-center hover:border-primary-400 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 transition-colors duration-200">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                </svg>
                <span class="mt-2 block text-sm font-medium text-gray-900">Audit Logs</span>
            </a>
        </div>
    </div>

    <!-- Recent Activity -->
    <div class="bg-white shadow-sm rounded-lg border border-gray-200">
        <div class="px-4 py-5 sm:p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Recent Activity</h3>
            <div class="flow-root">
                <ul role="list" class="-mb-8">
                    <li>
                        <div class="relative pb-8">
                            <span class="absolute left-4 top-4 -ml-px h-full w-0.5 bg-gray-200" aria-hidden="true"></span>
                            <div class="relative flex space-x-3">
                                <div>
                                    <span class="h-8 w-8 rounded-full bg-green-500 flex items-center justify-center ring-8 ring-white">
                                        <svg class="h-5 w-5 text-white" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                        </svg>
                                    </span>
                                </div>
                                <div class="flex min-w-0 flex-1 justify-between space-x-4 pt-1.5">
                                    <div>
                                        <p class="text-sm text-gray-500">System initialized with default users and roles</p>
                                    </div>
                                    <div class="whitespace-nowrap text-right text-sm text-gray-500">
                                        <time datetime="{{ now() }}">Just now</time>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </li>
                </ul>
            </div>
        </div>
    </div>
@endsection
