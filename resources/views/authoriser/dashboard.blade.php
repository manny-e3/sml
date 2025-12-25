@extends('layouts.app')

@section('title', 'Authoriser Dashboard')

@section('nav-links')
    <a href="{{ route('authoriser.dashboard') }}" class="inline-flex items-center border-b-2 border-primary-500 px-1 pt-1 text-sm font-medium text-gray-900">
        Dashboard
    </a>
    <a href="#" class="inline-flex items-center border-b-2 border-transparent px-1 pt-1 text-sm font-medium text-gray-500 hover:border-gray-300 hover:text-gray-700">
        Pending Approvals
    </a>
    <a href="#" class="inline-flex items-center border-b-2 border-transparent px-1 pt-1 text-sm font-medium text-gray-500 hover:border-gray-300 hover:text-gray-700">
        Audit Logs
    </a>
@endsection

@section('header')
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-gray-900">Authoriser Dashboard</h1>
        <div class="text-sm text-gray-500">
            Last login: {{ Auth::user()->last_login_at ? Auth::user()->last_login_at->diffForHumans() : 'First time' }}
        </div>
    </div>
@endsection

@section('content')
    <!-- Stats Overview -->
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4 mb-8">
        <div class="overflow-hidden rounded-lg bg-white px-4 py-5 shadow border border-gray-200 sm:p-6">
            <dt class="truncate text-sm font-medium text-gray-500">Pending Approvals</dt>
            <dd class="mt-1 text-3xl font-semibold tracking-tight text-yellow-600">0</dd>
        </div>

        <div class="overflow-hidden rounded-lg bg-white px-4 py-5 shadow border border-gray-200 sm:p-6">
            <dt class="truncate text-sm font-medium text-gray-500">Approved Today</dt>
            <dd class="mt-1 text-3xl font-semibold tracking-tight text-green-600">0</dd>
        </div>

        <div class="overflow-hidden rounded-lg bg-white px-4 py-5 shadow border border-gray-200 sm:p-6">
            <dt class="truncate text-sm font-medium text-gray-500">Rejected Today</dt>
            <dd class="mt-1 text-3xl font-semibold tracking-tight text-red-600">0</dd>
        </div>

        <div class="overflow-hidden rounded-lg bg-white px-4 py-5 shadow border border-gray-200 sm:p-6">
            <dt class="truncate text-sm font-medium text-gray-500">Total Reviewed</dt>
            <dd class="mt-1 text-3xl font-semibold tracking-tight text-gray-900">0</dd>
        </div>
    </div>

    <!-- Pending Approvals Alert -->
    <div class="mb-8 rounded-md bg-yellow-50 p-4 border border-yellow-200">
        <div class="flex">
            <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                </svg>
            </div>
            <div class="ml-3">
                <h3 class="text-sm font-medium text-yellow-800">No pending approvals</h3>
                <div class="mt-2 text-sm text-yellow-700">
                    <p>You have no pending actions to review at this time.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="mb-8">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Quick Actions</h2>
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <a href="#" class="relative block rounded-lg border-2 border-dashed border-gray-300 p-6 text-center hover:border-primary-400 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 transition-colors duration-200">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                </svg>
                <span class="mt-2 block text-sm font-medium text-gray-900">View Pending Approvals</span>
            </a>

            <a href="#" class="relative block rounded-lg border-2 border-dashed border-gray-300 p-6 text-center hover:border-primary-400 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 transition-colors duration-200">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <span class="mt-2 block text-sm font-medium text-gray-900">View Audit Logs</span>
            </a>

            <a href="#" class="relative block rounded-lg border-2 border-dashed border-gray-300 p-6 text-center hover:border-primary-400 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 transition-colors duration-200">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                </svg>
                <span class="mt-2 block text-sm font-medium text-gray-900">View Reports</span>
            </a>
        </div>
    </div>

    <!-- Recent Activity -->
    <div class="bg-white shadow-sm rounded-lg border border-gray-200">
        <div class="px-4 py-5 sm:p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Recent Approval Activity</h3>
            <div class="text-center py-12">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900">No recent activity</h3>
                <p class="mt-1 text-sm text-gray-500">Approval activities will appear here once you start reviewing submissions.</p>
            </div>
        </div>
    </div>
@endsection
