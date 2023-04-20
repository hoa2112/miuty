<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Telegram Bot Contact Form</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-light bg-light" style="margin-bottom: 50px;">
    <a class="navbar-brand" href="#">Chào mừng đến với thế giới bot của Hòa</a>
</nav>



<div class="container">
    <h5>Chọn nhóm chat: </h5>
    <div>
        <button class="btn btn-success" onclick="choice_group(1)"> Test bot miu tea </button>
        <button class="btn btn-warning" onclick="choice_group(2)"> Runner everywhere </button>
        <button class="btn btn-danger" onclick="choice_group(3)"> Tech WebSolution </button>
    </div>
    <hr>

    <div class="row">
        <div class="col-sm-10 offset-sm-1"  id="block-form-send-mess" style="display: none">
            <form action="{{ url('/send-message') }}" method="post">
                {{ csrf_field() }}
                <input type="hidden" name="index_group" id="index_group">
                <div class="form-group">
                    <label for="message">Message</label>
                    <textarea name="message" id="message" class="form-control" placeholder="Enter your query" rows="10"></textarea>
                </div>
                <div class="form-group">
                    <button type="submit" class="btn btn-primary">Submit</button>
                </div>
            </form>
        </div>
    </div>
</div>
</body>
</html>
<script
        src="https://code.jquery.com/jquery-3.6.4.js"
        integrity="sha256-a9jBBRygX1Bh5lt8GZjXDzyOB+bWve9EiO7tROUtj/E="
        crossorigin="anonymous"></script>
<script>
    function choice_group(index) {
        $('#block-form-send-mess').show();
        $('#index_group').val(index);
    }
</script>