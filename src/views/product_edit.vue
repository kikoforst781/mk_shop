<template>
  <div class="container mt-4">
    <h2 class="mb-3">รายการสินค้า</h2>

    <!-- 🔹 ปุ่มเพิ่ม + ตัวเลือกจำนวนแถว -->
    <div class="d-flex justify-content-between align-items-center mb-3">
      <button class="btn btn-primary" @click="openAddModal">
        <i class="bi bi-plus-circle"></i> เพิ่มสินค้าใหม่
      </button>

      <div class="d-flex align-items-center">
        <label class="me-2">แสดงแถวต่อหน้า:</label>
        <select v-model.number="itemsPerPage" class="form-select w-auto">
          <option :value="5">5</option>
          <option :value="10">10</option>
          <option :value="20">20</option>
          <option :value="50">50</option>
        </select>
      </div>
    </div>

    <!-- ✅ ตารางสินค้า -->
    <div class="table-responsive">
      <table class="table table-bordered table-striped table-hover align-middle">
        <thead class="table-primary">
          <tr>
            <th>ID</th>
            <th>หมวดหมู่</th>
            <th>ชื่อสินค้า</th>
            <th>รายละเอียด</th>
            <th>ราคา</th>
            <th>จำนวน</th>
            <th>รูปภาพ</th>
            <th style="width: 150px;">การจัดการ</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="loading">
            <td colspan="8" class="text-center">
              <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">กำลังโหลด...</span>
              </div>
              <p class="mt-2">กำลังโหลดข้อมูล...</p>
            </td>
          </tr>
          <tr v-else-if="paginatedProducts.length === 0">
            <td colspan="8" class="text-center text-muted">ไม่มีข้อมูลสินค้า</td>
          </tr>
          <tr v-else v-for="product in paginatedProducts" :key="product.product_id">
            <td>{{ product.product_id }}</td>
            <td>
              <span class="badge bg-info">
                {{ getCategoryName(product.category_id) }}
              </span>
            </td>
            <td>{{ product.product_name }}</td>
            <td>{{ product.description || '-' }}</td>
            <td class="text-end">{{ parseFloat(product.price).toFixed(2) }} บาท</td>
            <td class="text-center">{{ product.stock }}</td>
            <td class="text-center">
              <img
                v-if="product.image"
                :src="'http://localhost:8081/MK_SHOP/php_api/uploads/' + product.image"
                width="80"
                height="80"
                style="object-fit: cover; border-radius: 5px;"
                @error="handleImageError"
              />
              <span v-else class="text-muted">ไม่มีรูป</span>
            </td>
            <td class="text-center">
              <button 
                class="btn btn-warning btn-sm me-1 mb-1" 
                @click="openEditModal(product)"
                title="แก้ไข"
              >
                <i class="bi bi-pencil-square"></i>
              </button>
              <button 
                class="btn btn-danger btn-sm mb-1" 
                @click="deleteProduct(product.product_id)"
                title="ลบ"
              >
                <i class="bi bi-trash3"></i>
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div v-if="error" class="alert alert-danger">{{ error }}</div>

    <!-- ✅ ระบบแบ่งหน้า -->
    <nav v-if="totalPages > 1" class="mt-3">
      <ul class="pagination justify-content-center">
        <li class="page-item" :class="{ disabled: currentPage === 1 }">
          <button class="page-link" @click="prevPage" :disabled="currentPage === 1">
            ก่อนหน้า
          </button>
        </li>

        <li
          class="page-item"
          v-for="page in displayPages"
          :key="page"
          :class="{ active: currentPage === page }"
        >
          <button class="page-link" @click="goToPage(page)">{{ page }}</button>
        </li>

        <li class="page-item" :class="{ disabled: currentPage === totalPages }">
          <button class="page-link" @click="nextPage" :disabled="currentPage === totalPages">
            ถัดไป
          </button>
        </li>
      </ul>
    </nav>

    <!-- แสดงข้อมูลสรุป -->
    <div class="text-center text-muted mt-2">
      แสดง {{ startItem }} - {{ endItem }} จากทั้งหมด {{ products.length }} รายการ
    </div>

    <!-- ✅ Modal ใช้ทั้งเพิ่ม / แก้ไข -->
    <div class="modal fade" id="editModal" tabindex="-1">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header bg-primary text-white">
            <h5 class="modal-title">
              <i class="bi" :class="isEditMode ? 'bi-pencil-square' : 'bi-plus-circle'"></i>
              {{ isEditMode ? "แก้ไขสินค้า" : "เพิ่มสินค้าใหม่" }}
            </h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <form @submit.prevent="saveProduct">
              <div class="row">
                <!-- หมวดหมู่ -->
                <div class="col-md-6 mb-3">
                  <label class="form-label">
                    <i class="bi bi-tag"></i> หมวดหมู่ <span class="text-danger">*</span>
                  </label>
                  <select v-model="editForm.category_id" class="form-select" required>
                    <option value="">-- เลือกหมวดหมู่ --</option>
                    <option 
                      v-for="category in categories" 
                      :key="category.category_id"
                      :value="category.category_id"
                    >
                      {{ category.category_name }}
                    </option>
                  </select>
                </div>

                <!-- ชื่อสินค้า -->
                <div class="col-md-6 mb-3">
                  <label class="form-label">
                    <i class="bi bi-box"></i> ชื่อสินค้า <span class="text-danger">*</span>
                  </label>
                  <input 
                    v-model="editForm.product_name" 
                    type="text" 
                    class="form-control" 
                    placeholder="ระบุชื่อสินค้า"
                    required 
                  />
                </div>

                <!-- รายละเอียด -->
                <div class="col-12 mb-3">
                  <label class="form-label">
                    <i class="bi bi-file-text"></i> รายละเอียด
                  </label>
                  <textarea 
                    v-model="editForm.description" 
                    class="form-control"
                    rows="3"
                    placeholder="ระบุรายละเอียดสินค้า (ถ้ามี)"
                  ></textarea>
                </div>

                <!-- ราคา -->
                <div class="col-md-6 mb-3">
                  <label class="form-label">
                    <i class="bi bi-currency-exchange"></i> ราคา (บาท) <span class="text-danger">*</span>
                  </label>
                  <input 
                    v-model="editForm.price" 
                    type="number" 
                    step="0.01" 
                    class="form-control"
                    placeholder="0.00"
                    required 
                  />
                </div>

                <!-- จำนวน -->
                <div class="col-md-6 mb-3">
                  <label class="form-label">
                    <i class="bi bi-box-seam"></i> จำนวนสต็อก <span class="text-danger">*</span>
                  </label>
                  <input 
                    v-model="editForm.stock" 
                    type="number" 
                    class="form-control"
                    placeholder="0"
                    required 
                  />
                </div>

                <!-- รูปภาพ -->
                <div class="col-12 mb-3">
                  <label class="form-label">
                    <i class="bi bi-image"></i> รูปภาพ 
                    <span v-if="!isEditMode" class="text-danger">*</span>
                  </label>
                  <input
                    type="file"
                    @change="handleFileUpload"
                    class="form-control"
                    accept="image/*"
                    :required="!isEditMode"
                  />
                  <small class="text-muted">รองรับไฟล์: JPG, PNG, GIF (ขนาดไม่เกิน 5MB)</small>
                  
                  <!-- แสดงรูปเดิม (กรณีแก้ไข) -->
                  <div v-if="isEditMode && editForm.image" class="mt-3">
                    <p class="mb-2 fw-bold">รูปภาพปัจจุบัน:</p>
                    <img
                      :src="'http://localhost:8081/MK_SHOP/php_api/uploads/' + editForm.image"
                      class="img-thumbnail"
                      style="max-width: 200px;"
                    />
                  </div>

                  <!-- Preview รูปใหม่ -->
                  <div v-if="imagePreview" class="mt-3">
                    <p class="mb-2 fw-bold text-success">รูปภาพใหม่:</p>
                    <img
                      :src="imagePreview"
                      class="img-thumbnail"
                      style="max-width: 200px;"
                    />
                  </div>
                </div>
              </div>

              <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                  <i class="bi bi-x-circle"></i> ยกเลิก
                </button>
                <button type="submit" class="btn btn-success" :disabled="submitting">
                  <span v-if="submitting">
                    <span class="spinner-border spinner-border-sm me-2"></span>
                    กำลังบันทึก...
                  </span>
                  <span v-else>
                    <i class="bi bi-check-circle"></i>
                    {{ isEditMode ? "บันทึกการแก้ไข" : "บันทึกสินค้าใหม่" }}
                  </span>
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, onMounted, computed, watch } from "vue";
import axios from "axios";

export default {
  name: "ProductEdit",
  setup() {
    const products = ref([]);
    const categories = ref([]);
    const loading = ref(true);
    const error = ref(null);
    const isEditMode = ref(false);
    const submitting = ref(false);
    const editForm = ref({
      product_id: null,
      category_id: "",
      product_name: "",
      description: "",
      price: "",
      stock: "",
      image: ""
    });
    const newImageFile = ref(null);
    const imagePreview = ref(null);
    let modalInstance = null;

    // ✅ Pagination
    const currentPage = ref(1);
    const itemsPerPage = ref(10);

    const totalPages = computed(() =>
      Math.ceil(products.value.length / itemsPerPage.value)
    );

    const paginatedProducts = computed(() => {
      const start = (currentPage.value - 1) * itemsPerPage.value;
      return products.value.slice(start, start + itemsPerPage.value);
    });

    // แสดงเฉพาะหน้าที่เกี่ยวข้อง (ไม่เกิน 5 หน้า)
    const displayPages = computed(() => {
      const pages = [];
      const maxDisplay = 5;
      let start = Math.max(1, currentPage.value - Math.floor(maxDisplay / 2));
      let end = Math.min(totalPages.value, start + maxDisplay - 1);

      if (end - start < maxDisplay - 1) {
        start = Math.max(1, end - maxDisplay + 1);
      }

      for (let i = start; i <= end; i++) {
        pages.push(i);
      }
      return pages;
    });

    const startItem = computed(() => 
      products.value.length === 0 ? 0 : (currentPage.value - 1) * itemsPerPage.value + 1
    );

    const endItem = computed(() => 
      Math.min(currentPage.value * itemsPerPage.value, products.value.length)
    );

    const goToPage = (page) => {
      currentPage.value = page;
    };

    const nextPage = () => {
      if (currentPage.value < totalPages.value) currentPage.value++;
    };

    const prevPage = () => {
      if (currentPage.value > 1) currentPage.value--;
    };

    // รีเซ็ตหน้ากลับไปหน้า 1 เมื่อเปลี่ยนจำนวนแถวต่อหน้า
    watch(itemsPerPage, () => {
      currentPage.value = 1;
    });

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
    const fetchProducts = async () => {
      loading.value = true;
      try {
        const res = await axios.get("http://localhost:8081/MK_SHOP/php_api/api_product.php");
        products.value = res.data.success ? res.data.data : [];
      } catch (err) {
        error.value = "เกิดข้อผิดพลาดในการโหลดข้อมูล: " + err.message;
      } finally {
        loading.value = false;
      }
    };

    // ✅ หาชื่อหมวดหมู่จาก ID
    const getCategoryName = (categoryId) => {
      const category = categories.value.find(c => c.category_id == categoryId);
      return category ? category.category_name : "ไม่ระบุ";
    };

    // ✅ เปิด Modal เพิ่มสินค้า
    const openAddModal = () => {
      isEditMode.value = false;
      editForm.value = {
        product_id: null,
        category_id: "",
        product_name: "",
        description: "",
        price: "",
        stock: "",
        image: ""
      };
      newImageFile.value = null;
      imagePreview.value = null;
      const modalEl = document.getElementById("editModal");
      modalInstance = new window.bootstrap.Modal(modalEl);
      modalInstance.show();
      const fileInput = modalEl.querySelector('input[type="file"]');
      if (fileInput) fileInput.value = "";
    };

    // ✅ เปิด Modal แก้ไขสินค้า
    const openEditModal = (product) => {
      isEditMode.value = true;
      editForm.value = { ...product };
      newImageFile.value = null;
      imagePreview.value = null;
      const modalEl = document.getElementById("editModal");
      modalInstance = new window.bootstrap.Modal(modalEl);
      modalInstance.show();
    };

    // ✅ จัดการไฟล์รูปภาพ
    const handleFileUpload = (event) => {
      const file = event.target.files[0];
      if (file) {
        // ตรวจสอบขนาดไฟล์ (ไม่เกิน 5MB)
        if (file.size > 5 * 1024 * 1024) {
          alert("ไฟล์มีขนาดใหญ่เกินไป (ไม่เกิน 5MB)");
          event.target.value = "";
          return;
        }

        newImageFile.value = file;

        // สร้าง preview
        const reader = new FileReader();
        reader.onload = (e) => {
          imagePreview.value = e.target.result;
        };
        reader.readAsDataURL(file);
      }
    };

    // ✅ บันทึกสินค้า (เพิ่ม/แก้ไข)
    const saveProduct = async () => {
      if (!editForm.value.category_id) {
        alert("กรุณาเลือกหมวดหมู่");
        return;
      }

      submitting.value = true;

      const formData = new FormData();
      formData.append("action", isEditMode.value ? "update" : "add");
      if (isEditMode.value) formData.append("product_id", editForm.value.product_id);
      formData.append("category_id", editForm.value.category_id);
      formData.append("product_name", editForm.value.product_name);
      formData.append("description", editForm.value.description);
      formData.append("price", editForm.value.price);
      formData.append("stock", editForm.value.stock);
      if (newImageFile.value) formData.append("image", newImageFile.value);

      try {
        const res = await axios.post(
          "http://localhost:8081/MK_SHOP/php_api/api_product.php",
          formData,
          {
            headers: { "Content-Type": "multipart/form-data" }
          }
        );

        if (res.data.message) {
          alert(res.data.message);
          fetchProducts();
          modalInstance.hide();
        } else if (res.data.error) {
          alert(res.data.error);
        }
      } catch (err) {
        alert("เกิดข้อผิดพลาด: " + err.message);
      } finally {
        submitting.value = false;
      }
    };

    // ✅ ลบสินค้า
    const deleteProduct = async (id) => {
      if (!confirm("คุณแน่ใจหรือไม่ที่จะลบสินค้านี้?")) return;

      const formData = new FormData();
      formData.append("action", "delete");
      formData.append("product_id", id);

      try {
        const res = await axios.post(
          "http://localhost:8081/MK_SHOP/php_api/api_product.php",
          formData
        );

        if (res.data.message) {
          alert(res.data.message);
          fetchProducts();
        } else if (res.data.error) {
          alert(res.data.error);
        }
      } catch (err) {
        alert("เกิดข้อผิดพลาด: " + err.message);
      }
    };

    // ✅ จัดการ error รูปภาพ
    const handleImageError = (event) => {
      event.target.src = "https://via.placeholder.com/80x80?text=No+Image";
    };

    onMounted(() => {
      fetchCategories();
      fetchProducts();
    });

    return {
      products,
      categories,
      loading,
      error,
      editForm,
      isEditMode,
      submitting,
      imagePreview,
      openAddModal,
      openEditModal,
      handleFileUpload,
      saveProduct,
      deleteProduct,
      getCategoryName,
      handleImageError,
      currentPage,
      totalPages,
      paginatedProducts,
      itemsPerPage,
      displayPages,
      startItem,
      endItem,
      goToPage,
      nextPage,
      prevPage
    };
  }
};
</script>

<style scoped>
.table th {
  background-color: #0d6efd;
  color: white;
}

.badge {
  font-size: 0.85rem;
  padding: 0.4em 0.8em;
}

.modal-header {
  border-bottom: 3px solid #0d6efd;
}

.img-thumbnail {
  border: 2px solid #dee2e6;
}

.btn-sm {
  padding: 0.25rem 0.5rem;
  font-size: 0.875rem;
}
</style>