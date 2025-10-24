<template>
  <div class="container mt-5" style="max-width:400px;">
    <h3 class="text-center mb-4">🔐 เข้าสู่ระบบพนักงาน</h3>

    <div class="card p-4 shadow">
      <div class="mb-3">
        <label class="form-label">ชื่อผู้ใช้</label>
        <input 
          v-model="username" 
          type="text" 
          class="form-control"
          placeholder="กรอกชื่อผู้ใช้"
          @keyup.enter="login"
          :disabled="loading"
        />
      </div>

      <div class="mb-3">
        <label class="form-label">รหัสผ่าน</label>
        <input 
          v-model="password" 
          type="password" 
          class="form-control"
          placeholder="กรอกรหัสผ่าน"
          @keyup.enter="login"
          :disabled="loading"
        />
      </div>

      <button 
        @click="login" 
        class="btn btn-primary w-100"
        :disabled="loading"
      >
        <span v-if="loading">
          <span class="spinner-border spinner-border-sm me-2"></span>
          กำลังเข้าสู่ระบบ...
        </span>
        <span v-else>เข้าสู่ระบบ</span>
      </button>

      <div v-if="error" class="alert alert-danger mt-3 mb-0">
        {{ error }}
      </div>
    </div>
  </div>
</template>

<script>
import axios from "axios";

export default {
  name: "LoginView",
  data() {
    return {
      username: "",
      password: "",
      error: "",
      loading: false
    };
  },
  methods: {
    async login() {
      // ตรวจสอบข้อมูลก่อน submit
      if (!this.username || !this.password) {
        this.error = "กรุณากรอกชื่อผู้ใช้และรหัสผ่าน";
        return;
      }

      this.loading = true;
      this.error = "";

      try {
        const res = await axios.post(
          "http://localhost:8081/MK_SHOP/php_api/login.php",
          {
            username: this.username,
            password: this.password,
          }
        );

        if (res.data.success) {
          // เก็บข้อมูลพนักงานที่ login สำเร็จ
          localStorage.setItem("adminLogin", "true");
          localStorage.setItem("employeeId", res.data.employee.id);
          localStorage.setItem("employeeName", res.data.employee.name);
          localStorage.setItem("employeePosition", res.data.employee.position);

          // แสดงข้อความต้อนรับ (optional)
          console.log(`ยินดีต้อนรับ ${res.data.employee.name}`);

          // ไปหน้าแสดงออเดอร์
          this.$router.push("/show_orders");
        } else {
          this.error = res.data.message || "ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง";
        }
      } catch (err) {
        console.error("Login error:", err);
        this.error = "เกิดข้อผิดพลาดในการเชื่อมต่อ กรุณาลองใหม่อีกครั้ง";
      } finally {
        this.loading = false;
      }
    },
  },
  mounted() {
    // ถ้า login อยู่แล้ว ให้ redirect ไปหน้า orders
    if (localStorage.getItem("adminLogin") === "true") {
      this.$router.push("/show_orders");
    }
  }
};
</script>

<style scoped>
.card {
  border-radius: 12px;
  border: none;
}

.form-control:focus {
  border-color: #0d6efd;
  box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
}

.btn-primary {
  padding: 10px;
  font-weight: 500;
}

.btn-primary:disabled {
  cursor: not-allowed;
}

.alert {
  border-radius: 8px;
  font-size: 0.9rem;
}

.spinner-border-sm {
  width: 1rem;
  height: 1rem;
}
</style>