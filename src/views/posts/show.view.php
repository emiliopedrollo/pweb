@extends('layouts.app')

@section('content')
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-body">

                        <div class="container-fluid">
                            <div class="row">
                                <div class="col">
                                    <h2>
                                        {{ 'Vizualizar postagem' }}
                                    </h2>
                                </div>
                            </div>
                        </div>

                        @if (session('status'))
                            <div class="alert alert-success" role="alert">
                                {{ session('status') }}
                            </div>
                        @endif

                        <div class="container">

                            <div class="row">
                                <div class="mb-3">
                                    <label for="post-schedule" class="form-label">{{ 'Agendamento' }}</label>
                                    <input type="text" class="form-control-plaintext" id="post-schedule"
                                           value="{{ $post->schedule->format('d/m/Y H:i:s') }}" readonly>
                                </div>
                            </div>
                            <div class="row gx-3">
                                <div class="mb-3 col col-12">
                                    <label for="post-content" class="form-label">{{ __('Conteúdo') }}</label>
                                    <textarea class="form-control-plaintext" id="post-content" rows="5" readonly>{{
                                        $post->content
                                    }}</textarea>
                                </div>
                            </div>
                            <a href="{{ route('post.edit',['post' => $post->id]) }}"
                               class="btn btn-outline-primary">{{ 'Editar' }}</a>
                            <a href="{{ route('posts') }}"
                               class="btn btn-outline-secondary">{{ 'Voltar' }}</a>
                        </div>
                    </div>
                </div>
                <h3 class="mt-3">
                    {{ 'Comentários' }}
                </h3>
                <div class="card mb-2">
                    <div class="card-body">
                        <div class="container-fluid">
                            <form method="POST" action="{{ route('comment.store',['post' => $post->id]) }}">
                            <div class="row">
                                <div class="input-group">
                                    <!--suppress HtmlFormInputWithoutLabel -->
                                    <input type="text" class="form-control" placeholder="Comente aqui..." name="content" />
                                    <button type="submit" class="btn btn-outline-primary">{{ 'Comentar' }}</button>
                                </div>
                            </div>
                            </form>
                        </div>
                    </div>
                </div>
                @forelse($post->comments as $comment)
                    <div class="card mb-2">
                        <div class="card-body">
                            <div class="container-fluid">
                                <div class="row">
                                    @if($comment->id === ($editing_comment??null)?->id)
                                        <form method="POST"
                                              action="{{ route('comment.update',[
                                                    'post' => $post->id, 'comment' => $comment->id
                                                ]) }}">
                                            <div class="input-group">
                                                <!--suppress HtmlFormInputWithoutLabel -->
                                                <input type="text" class="form-control" autofocus required
                                                       value="{{ $comment->content }}" name="content"/>
                                                <button type="submit" class="btn btn-outline-primary">{{ 'Alterar' }}</button>
                                                <a href="{{ route('post.show',[ 'post' => $post->id ]) }}"
                                                   class="btn btn-outline-secondary">{{ 'Cancelar' }}</a>
                                            </div>
                                        </form>
                                    @else
                                    <form method="POST"
                                          action="{{ route('comment.destroy',[
                                                    'post' => $post->id, 'comment' => $comment->id
                                                ]) }}">
                                        @method('delete')
                                        <div class="input-group">
                                            <span class="input-group-text flex-fill">{{ $comment->content }}</span>
                                            <a href="{{ route('comment.edit',[
                                                    'post' => $post->id, 'comment' => $comment->id
                                                ]) }}"
                                               class="btn btn-outline-secondary">{{ 'Editar' }}</a>
                                            <a href="{{ route('comment.destroy',[
                                                    'post' => $post->id, 'comment' => $comment->id
                                                ]) }}"
                                               onclick="event.preventDefault();this.closest('form').submit();"
                                               class="btn btn-outline-danger">{{ 'Deletar' }}</a>
                                        </div>
                                    </form>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                @empty
                    {{ 'Esta publicação ainda não tem nenhum comentário' }}
                @endforelse
            </div>
        </div>
    </div>

@endsection
