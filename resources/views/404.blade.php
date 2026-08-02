@extends('layout.layout', [
    'title' => "ناظر یدک"
])
@section('main_content')

    <main><!-- start main -->
        <div class="container">
            <div class="error-box"><!-- start 404 error box -->
                <div class="row">
                    <div class="col-12 text-center">
                        <p>صفحه ای که به دنبال آن بودید پیدا نشد !</p>
                        <a href="/" class="btn btn-info font-14 py-2 px-3">برو به صفحه اصلی</a>
                        <img src="/assets/images/404.png" class="img-fluid d-block mx-auto">
                    </div>
                </div>
            </div><!-- end 404 error box -->
        </div>
    </main><!-- end main -->
@endsection
