// myprofile-btn.js

// Đảm bảo script này được thực thi sau khi DOM đã sẵn sàng và jQuery đã được tải.
$(function () {
    // Lắng nghe sự kiện click trên nút "My Profile"
    $('#btn-myprofile').on('click', function () {
        window.location.href = '/ViecNhe/jobseekerprofile/ho_so_ung_vien.php';

        // 1. Thay đổi trạng thái active của nút (làm nút đỏ)
        // Dù chuyển trang, việc này có thể hữu ích để người dùng thấy nút nào đã được bấm
        // trước khi trang chuyển hướng.
        $('.sidebar button').removeClass('active'); // Xóa class 'active' khỏi tất cả các nút sidebar khác
        $(this).addClass('active'); // Thêm class 'active' vào nút vừa được click

        // 2. Chuyển hướng trình duyệt sang trang mới: ho_so_ung_vien.php
        // Sử dụng đường dẫn tuyệt đối để đảm bảo chuyển hướng đúng

        // Không cần return false ở đây vì window.location.href đã làm nhiệm vụ chuyển hướng.
        // Nếu bạn muốn ngăn chặn bất kỳ hành vi mặc định nào khác của sự kiện click
        // (ví dụ, nếu nút là một thẻ <a> hoặc <button type="submit"> trong một form),
        // bạn có thể thêm e.preventDefault() và truyền 'e' vào function(e).
    });
});