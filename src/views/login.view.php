<html lang="pt-br">
<head>
    <meta charset="utf-8">
    <title>Login</title>
</head>
<body>
<div>
    <h1>Acessar</h1>
    {{ $error }}
    <form method="post" action="/login">
        <label for="email">Usuário: </label>
        <input type="text" name="email" id="email">
        <label for="password">Senha: </label>
        <input type="password" name="password" id="password">
        <button type="submit" name="opcao"
                value="Entrar">Entrar
        </button>
    </form>
    <br><a href="/register">Cadastrar Usuário</a>
</div>
</body>
</html>
