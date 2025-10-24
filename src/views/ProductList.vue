<template>
  <div class="container my-5">
    <h2 class="text-center mb-4">เมนูสินค้า</h2>

    <!-- ส่วนเลือกโต๊ะ -->
    <div class="mb-4 text-center">
      <label class="me-2 fw-bold">เลือกโต๊ะ:</label>
      <select v-model="selectedTable" class="form-select d-inline-block w-auto">
        <option disabled value="">-- เลือกโต๊ะ --</option>
        <option v-for="table in tables" :key="table" :value="table">
          โต๊ะ {{ table }}
        </option>
      </select>
    </div>

    <!-- ✨ ส่วนเลือกหมวดหมู่ -->
    <div class="mb-4 text-center">
      <label class="me-2 fw-bold">เลือกหมวดหมู่:</label>
      <div class="btn-group" role="group">
        <button 
          type="button" 
          class="btn"
          :class="selectedCategory === '' ? 'btn-success' : 'btn-outline-success'"
          @click="filterByCategory('')"
        >
          ทั้งหมด
        </button>
        <button 
          v-for="category in categories" 
          :key="category.category_id"
          type="button" 
          class="btn"
          :class="selectedCategory === category.category_id ? 'btn-success' : 'btn-outline-success'"
          @click="filterByCategory(category.category_id)"
        >
          {{ category.category_name }}
        </button>
      </div>
    </div>

    <!-- แสดงสถานะ Loading -->
    <div v-if="loading" class="text-center my-5">
      <div class="spinner-border text-primary" role="status">
        <span class="visually-hidden">Loading...</span>
      </div>
      <p class="mt-2">กำลังโหลดสินค้า...</p>
    </div>

    <!-- แสดง Error -->
    <div v-if="error" class="alert alert-danger">{{ error }}</div>

    <!-- แสดงสินค้า -->
    <div class="row" v-if="!loading && !error">
      <div v-if="products.length === 0" class="col-12 text-center text-muted my-5">
        <h5>ไม่พบสินค้าในหมวดหมู่นี้</h5>
      </div>
      <div class="col-md-3 col-sm-6" v-for="product in products" :key="product.product_id">
        <div class="card shadow-sm mb-4 h-100">
          <img
            :src="'http://localhost:8081/MK_SHOP/php_api/uploads/' + product.image"
            class="card-img-top"
            style="height: 200px; object-fit: cover;"
            :alt="product.product_name"
            @error="handleImageError"
          />
          <div class="card-body text-center d-flex flex-column">
            <h5 class="card-title">{{ product.product_name }}</h5>
            <p class="card-text text-success fw-bold fs-5">{{ product.price }} บาท</p>
            <button class="btn btn-success mt-auto" @click="addToCart(product)">
              สั่งซื้อ
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- แสดงตะกร้าสินค้า -->
    <div class="mt-5" v-if="cart.length > 0">
      <h4 class="mb-3">🧺 ตะกร้าสินค้า (โต๊ะ {{ selectedTable || '-' }})</h4>

      <div class="table-responsive">
        <table class="table table-bordered align-middle">
          <thead class="table-light">
            <tr>
              <th>สินค้า</th>
              <th>ราคา</th>
              <th style="width:180px;">จำนวน</th>
              <th>รวม</th>
              <th style="width:80px;">ลบ</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(item, index) in cart" :key="index">
              <td>{{ item.product_name }}</td>
              <td>{{ item.price }} บาท</td>
              <td class="text-center">
                <div class="btn-group" role="group">
                  <button
                    class="btn btn-sm btn-outline-secondary"
                    @click="decreaseQty(item)"
                  >
                    -
                  </button>
                  <button class="btn btn-sm btn-outline-secondary" disabled>
                    {{ item.quantity }}
                  </button>
                  <button
                    class="btn btn-sm btn-outline-secondary"
                    @click="increaseQty(item)"
                  >
                    +
                  </button>
                </div>
              </td>
              <td class="fw-bold">{{ (item.price * item.quantity).toFixed(2) }} บาท</td>
              <td class="text-center">
                <button
                  class="btn btn-danger btn-sm"
                  @click="removeFromCart(index)"
                >
                  ลบ
                </button>
              </td>
            </tr>
          </tbody>
          <tfoot class="table-light">
            <tr>
              <td colspan="3" class="text-end fw-bold">รวมทั้งหมด</td>
              <td colspan="2" class="fw-bold text-success fs-5">
                {{ totalPrice.toFixed(2) }} บาท
              </td>
            </tr>
          </tfoot>
        </table>
      </div>

      <!-- ปุ่มยืนยันสั่งซื้อ -->
      <div class="text-end mt-3">
        <button class="btn btn-danger me-2" @click="clearCart">
          ล้างตะกร้า
        </button>
        <button class="btn btn-primary btn-lg" @click="submitOrder" :disabled="submitting">
          <span v-if="submitting">
            <span class="spinner-border spinner-border-sm me-2"></span>
            กำลังส่งออเดอร์...
          </span>
          <span v-else>
            ยืนยันการสั่งซื้อ
          </span>
        </button>
      </div>
    </div>

    <!-- ข้อความเมื่อตะกร้าว่าง -->
    <div v-else class="alert alert-info text-center mt-5">
      <h5>🛒 ยังไม่มีสินค้าในตะกร้า</h5>
      <p class="mb-0">กรุณาเลือกสินค้าที่ต้องการสั่งซื้อ</p>
    </div>
  </div>
</template>

<script>
import { ref, computed, onMounted } from "vue";
import axios from "axios";

export default {
  name: "ProductList",
  setup() {
    const products = ref([]);
    const categories = ref([]);
    const cart = ref([]);
    const selectedTable = ref("");
    const selectedCategory = ref("");
    const tables = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10];
    const loading = ref(true);
    const error = ref(null);
    const submitting = ref(false);

    // ✅ ดึงข้อมูลหมวดหมู่
    const fetchCategories = async () => {
      try {
        const response = await axios.get(
          "http://localhost:8081/MK_SHOP/php_api/get_categories.php"
        );
        if (response.data.success) {
          categories.value = response.data.data;
        }
      } catch (err) {
        console.error("Error fetching categories:", err);
      }
    };

    // ✅ ดึงข้อมูลสินค้า
    const fetchProducts = async (categoryId = "") => {
      loading.value = true;
      error.value = null;
      
      try {
        let url = "http://localhost:8081/MK_SHOP/php_api/show_product.php";
        if (categoryId) {
          url += `?category_id=${categoryId}`;
        }
        
        const response = await axios.get(url);
        
        if (response.data.success) {
          products.value = response.data.data;
        } else {
          error.value = response.data.message;
        }
      } catch (err) {
        error.value = "เกิดข้อผิดพลาดในการโหลดสินค้า: " + err.message;
        console.error("Error fetching products:", err);
      } finally {
        loading.value = false;
      }
    };

    // ✅ กรองสินค้าตามหมวดหมู่
    const filterByCategory = (categoryId) => {
      selectedCategory.value = categoryId;
      fetchProducts(categoryId);
    };

    // ✅ เพิ่มสินค้าเข้าตะกร้า
    const addToCart = (product) => {
      if (!selectedTable.value) {
        alert("⚠️ กรุณาเลือกโต๊ะก่อนสั่งสินค้า");
        return;
      }

      const existing = cart.value.find(
        (item) => item.product_id === product.product_id
      );

      if (existing) {
        existing.quantity++;
        alert(`✅ เพิ่มจำนวน "${product.product_name}" แล้ว (${existing.quantity} ชิ้น)`);
      } else {
        cart.value.push({
          product_id: product.product_id,
          product_name: product.product_name,
          price: parseFloat(product.price),
          quantity: 1,
        });
        alert(`✅ เพิ่ม "${product.product_name}" ลงในตะกร้าแล้ว`);
      }
    };

    // ✅ เพิ่มจำนวนสินค้า
    const increaseQty = (item) => {
      item.quantity++;
    };

    // ✅ ลดจำนวนสินค้า
    const decreaseQty = (item) => {
      if (item.quantity > 1) {
        item.quantity--;
      } else {
        if (confirm("ต้องการลบสินค้านี้ออกจากตะกร้าหรือไม่?")) {
          const index = cart.value.indexOf(item);
          if (index !== -1) cart.value.splice(index, 1);
        }
      }
    };

    // ✅ ลบสินค้าออกจากตะกร้า
    const removeFromCart = (index) => {
      if (confirm("ยืนยันการลบสินค้านี้หรือไม่?")) {
        cart.value.splice(index, 1);
      }
    };

    // ✅ ล้างตะกร้า
    const clearCart = () => {
      if (confirm("ต้องการล้างสินค้าทั้งหมดในตะกร้าหรือไม่?")) {
        cart.value = [];
        alert("✅ ล้างตะกร้าเรียบร้อยแล้ว");
      }
    };

    // ✅ คำนวณราคารวมทั้งหมด
    const totalPrice = computed(() =>
      cart.value.reduce((sum, item) => sum + item.price * item.quantity, 0)
    );

    // ✅ จัดการ error รูปภาพ
    const handleImageError = (event) => {
      event.target.src = "https://via.placeholder.com/200x200?text=No+Image";
    };

    // ✅ ยืนยันการสั่งซื้อ
    const submitOrder = async () => {
      if (!selectedTable.value) {
        alert("⚠️ กรุณาเลือกโต๊ะก่อนสั่งสินค้า");
        return;
      }

      if (cart.value.length === 0) {
        alert("⚠️ กรุณาเพิ่มสินค้าในตะกร้าก่อนสั่งซื้อ");
        return;
      }

      const orderData = {
        table_no: selectedTable.value,
        items: cart.value.map((item) => ({
          product_id: item.product_id,
          product_name: item.product_name,
          quantity: item.quantity,
          price: item.price,
        })),
        total: totalPrice.value,
      };

      submitting.value = true;

      try {
        const response = await axios.post(
          "http://localhost:8081/MK_SHOP/php_api/order.php",
          orderData
        );

        if (response.data.success) {
          alert("✅ สั่งซื้อสำเร็จ!\n" + 
                `โต๊ะ: ${selectedTable.value}\n` +
                `ยอดรวม: ${totalPrice.value.toFixed(2)} บาท`);
          cart.value = [];
          selectedTable.value = "";
        } else {
          alert("❌ " + response.data.message);
        }
      } catch (error) {
        alert("เกิดข้อผิดพลาด: " + error.message);
        console.error("Error submitting order:", error);
      } finally {
        submitting.value = false;
      }
    };

    // โหลดข้อมูลเมื่อเริ่มต้น
    onMounted(() => {
      fetchCategories();
      fetchProducts();
    });

    return {
      products,
      categories,
      cart,
      selectedTable,
      selectedCategory,
      tables,
      totalPrice,
      loading,
      error,
      submitting,
      addToCart,
      increaseQty,
      decreaseQty,
      removeFromCart,
      clearCart,
      submitOrder,
      filterByCategory,
      handleImageError,
    };
  },
};
</script>

<style scoped>
.card {
  transition: transform 0.2s, box-shadow 0.2s;
  border: none;
  border-radius: 10px;
}

.card:hover {
  transform: translateY(-5px);
  box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
}

.card-img-top {
  border-radius: 10px 10px 0 0;
}

.btn-group .btn {
  min-width: 80px;
  font-weight: 500;
}

.btn-group .btn:hover {
  transform: scale(1.05);
}

.table td,
.table th {
  vertical-align: middle;
}

.table-responsive {
  border-radius: 8px;
  overflow: hidden;
}

.form-select {
  border-radius: 8px;
  padding: 8px 16px;
}

.alert {
  border-radius: 8px;
}

.btn-success {
  transition: all 0.3s;
}

.btn-success:hover {
  transform: scale(1.05);
  box-shadow: 0 4px 12px rgba(25, 135, 84, 0.3);
}
</style>