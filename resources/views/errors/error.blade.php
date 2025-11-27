<!DOCTYPE html>
<html>
<head>
    <title>Error</title>
</head>
<body>
<h1>An error occurred</h1>
@isset($error)
    <p>{{ $error->getMessage() }}</p>
@endisset
</body>
</html>
