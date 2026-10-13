<style>
    .avatar-wrapper {
  position: relative;
  width: 100px; /* hoặc tuỳ bạn */
  height: 100px;
}

.avatar-circle img {
  width: 100px;
  height: 100px;
  border-radius: 50%;
  object-fit: cover;
}

.camera-icon {
  position: absolute;
  bottom: 0;
  right: 0;
  width: 24px; /* tuỳ chỉnh kích cỡ */
  height: 24px;
  background: white;
  border-radius: 50%;
  padding: 2px;
  box-shadow: 0 0 2px rgba(0,0,0,0.3);
}

</style>
<div class="avatar-wrapper">
  <div class="avatar-circle">
    <img src="image/defaultavatar.jpg" alt="Avatar">
  </div>
  <img src="image/camera.svg" class="camera-icon" alt="Camera">
</div>