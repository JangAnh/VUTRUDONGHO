<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lunar Veil</title>
  <link rel="shortcut icon" href="assets/Img/logo.png" type="image/x-icon">
    <link rel="stylesheet" href="assets/css/about_us.css">
    <link rel="stylesheet" href="assets/CSS/header.css">
    <link rel="stylesheet" href="assets/CSS/footer.css">
    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100;0,300;0,400;0,500;0,700;0,900;1,100;1,300;1,400;1,500;1,700;1,900&amp;display=swap&amp;_cacheOverride=1679484892371"
        data-tag="font">
    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Inter:wght@100;200;300;400;500;600;700;800;900&amp;display=swap"
        data-tag="font">
</head>
  <!--Start: Header-->
  <div id="bar-header">
    <?php
    include("components/header.php");
    ?>
  </div>
  <!--End: Header-->
<body style="position: relative;">
    <div id="container-aboutUs" style="position: relative;top:50px;height: fit-content;width: 100%;display: flex;flex-direction: column;align-items: center;">
        <img src="assets/Img/hoangImg/imgs/banner_about_us.png" width="100%" alt="">
        <div id="main-aboutUs" style="display: flex;flex-direction: row;">
            <div id="img-aboutUs-div" style="">
                <img id="img-aboutUs" src="assets/Img/hoangImg/imgs/apple_banner2.png" width="300" alt="">
            </div>
            <div id="content-aboutUs" style="display: flex;align-items: center;">
                <p id="text-content-aboutUs" style="text-align: justify;margin-right: 20px;margin-left: 20px;">
                    Lunar Veil là một trang web bán hàng trực tuyến chuyên về các loại đồng hồ cao cấp, đa dạng về
                    mẫu mã, kiểu dáng và thương hiệu. Với phương châm "Chất lượng đảm bảo - Dịch vụ hoàn hảo", chúng tôi
                    cam kết mang đến cho khách hàng những sản phẩm đồng hồ chính hãng, chất lượng cao và giá cả cạnh
                    tranh nhất trên thị trường. Ngoài ra, Lunar Veil còn có đội ngũ chuyên viên tư vấn nhiệt tình và
                    chuyên nghiệp để giúp đỡ khách hàng lựa chọn được sản phẩm phù hợp với nhu cầu và sở thích của mình.
                    Hơn nữa, chúng tôi luôn đặt sự hài lòng của khách hàng lên hàng đầu, bằng cách cung cấp các dịch vụ
                    hậu mãi tốt nhất, đảm bảo quyền lợi và sự hài lòng của khách hàng.
                </p>
            </div>
        </div>
        <a name="He_thong_cua_hang_tren_toan_quoc"></a>
        <a name="He_thong_cua_hang_mien_bac"></a>
        <img src="assets/Img/hoangImg/imgs/banner_hethongcuahang.png" width="100%" alt="">
        <div id="google-map" style="width: 100%; height: 600px; margin: 20px 0;">
            <iframe src="https://www.google.com/maps/d/embed?mid=1f7Jd4zZZCVCIjjIMW_WtUw6IjRnqtdM&ehbc=2E312F" width="1540" height="580"></iframe>
        </div>

    </div>
      <!--Start: Footer-->
  <div id="my-footer" style="margin-top: 50px;">
    <?php
    include("components/footer.php");
    ?>
  </div>
  <!--End: Footer-->
    <!--start Hiện thanh line-->
    <script>
    var lineHome = document.getElementById("navbarAbout");

    lineHome.style.borderBottom = '2px solid #fff';
    lineHome.style.paddingBottom = '1.15px';
  </script>
  <!--end Hiện thanh line-->
<script>
    function showMap(id) {
        const maps = document.querySelectorAll('.map-container');
        maps.forEach(m => m.classList.remove('show-map'));
        
        document.getElementById(id).classList.add('show-map');
    }
</script>
  <!-- FACEBOOK FLOAT BUTTON -->
  <?php
  include($_SERVER['DOCUMENT_ROOT'] . "/vutrudongho/vutrudongho/components/FB-social-chat.php");
  ?>
  <!-- TIDIO CHAT BUTTON -->
  <script src="//code.tidio.co/3ez9gbhu2mescwypayav1eg1e1ttvqnf.js" async></script>
</body>

</html>