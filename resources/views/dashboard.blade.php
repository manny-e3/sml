@extends('layouts.app')

@section('title', 'Dashboard')

@section('nav-links')
    <li class="nav-item">
        <a class="nav-link active" href="{{ route('dashboard') }}">
            <i class="bi bi-speedometer2 me-1"></i>Dashboard
        </a>
    </li>
@endsection

@section('header')
    <div class="d-flex justify-content-between align-items-center">
        <h2 class="mb-0 fw-bold">Dashboard</h2>
        <small class="text-muted">
            Last login: {{ Auth::user()->last_login_at ? Auth::user()->last_login_at->diffForHumans() : 'First time' }}
        </small>
    </div>
@endsection

@section('content')
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="bi bi-person-circle me-2"></i>
                        Welcome, {{ Auth::user()->full_name }}!
                    </h5>
                </div>
                <div class="card-body p-5 text-center">
                    <div class="mb-4">
                        <div class="bg-primary bg-opacity-10 rounded-circle d-inline-flex p-4 mb-3">
                            <i class="bi bi-shield-check fs-1 text-primary"></i>
                        </div>
                        <h3 class="fw-bold mb-2">SMLARS</h3>
                        <p class="text-muted">Security Master List and Auction Result System</p>
                    </div>

                    <div class="alert alert-info" role="alert">
                        <i class="bi bi-info-circle me-2"></i>
                        <strong>Your Role:</strong> {{ Auth::user()->getRoleNames()->first() }}
                    </div>

                    <div class="mt-4">
                        <p class="text-muted mb-3">Quick Links:</p>
                        <div class="d-flex gap-2 justify-content-center flex-wrap">
                            @if(Auth::user()->isSuperAdmin())
                                <a href="{{ route('admin.dashboard') }}" class="btn btn-primary">
                                    <i class="bi bi-speedometer2 me-1"></i>Admin Dashboard
                                </a>
                            @endif

                            @if(Auth::user()->canBeInputter())
                                <a href="{{ route('inputter.dashboard') }}" class="btn btn-primary">
                                    <i class="bi bi-speedometer2 me-1"></i>Inputter Dashboard
                                </a>
                            @endif

                            @if(Auth::user()->canBeAuthoriser())
                                <a href="{{ route('authoriser.dashboard') }}" class="btn btn-primary">
                                    <i class="bi bi-speedometer2 me-1"></i>Authoriser Dashboard
                                </a>
                            @endif
                        </div>
                    </div>

                    <hr class="my-4">

                    <div class="row text-start">
                        <div class="col-md-6">
                            <h6 class="fw-bold mb-3">Your Information</h6>
                            <ul class="list-unstyled">
                                <li class="mb-2">
                                    <i class="bi bi-envelope text-primary me-2"></i>
                                    <strong>Email:</strong> {{ Auth::user()->email }}
                                </li>
                                <li class="mb-2">
                                    <i class="bi bi-building text-primary me-2"></i>
                                    <strong>Department:</strong> {{ Auth::user()->department ?? 'N/A' }}
                                </li>
                                <li class="mb-2">
                                    <i class="bi bi-person-badge text-primary me-2"></i>
                                    <strong>Employee ID:</strong> {{ Auth::user()->employee_id ?? 'N/A' }}
                                </li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <h6 class="fw-bold mb-3">System Information</h6>
                            <ul class="list-unstyled">
                                <li class="mb-2">
                                    <i class="bi bi-calendar-check text-primary me-2"></i>
                                    <strong>Member Since:</strong> {{ Auth::user()->created_at->format('M d, Y') }}
                                </li>
                                <li class="mb-2">
                                    <i class="bi bi-clock-history text-primary me-2"></i>
                                    <strong>Last Login:</strong> {{ Auth::user()->last_login_at ? Auth::user()->last_login_at->format('M d, Y H:i') : 'First time' }}
                                </li>
                                <li class="mb-2">
                                    <i class="bi bi-check-circle text-success me-2"></i>
                                    <strong>Status:</strong> <span class="badge bg-success">Active</span>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
