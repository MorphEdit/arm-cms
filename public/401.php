<link rel="stylesheet" href="assets/css/main.css">

<div class="container">
    <div class="page-403">
        <div class="page-title-403">
            401
        </div>

        <div class="page-subtitle-403">
            Access Denied: Login required
        </div>

        <button class="btn location" onclick="window.location.href='login.php';">
            ไปยังหน้า Login
        </button>
    </div>
</div>

<style>
    .page-title-403 {
        font-size: 10rem;
        text-align: center;
    }

    .page-subtitle-403 {
        font-size: 3rem;
        text-align: center;
    }

    .location {
        display: flex;
        margin: auto;
    }

    .page-403 {
        gap: 2rem;
        display: grid;
        background-color: #fff;
        border-radius: 2rem;
        padding: 2rem;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        width: 90%;
    }

    .container {
        display: flex;
        justify-content: center;
        align-items: center;
        height: 100vh;
    }
</style>