<?php
namespace App\Controllers\Inventory;

use App\Core\Controller;
use App\Core\Session;
use App\Models\Supplier;
use App\Models\WholesaleProduct;
use App\Models\Purchase;
use App\Models\PurchasePayment;

class InventoryController extends Controller
{
    private $supplierModel;
    private $productModel;
    private $purchaseModel;
    private $paymentModel;

    public function __construct()
    {
        parent::__construct();
        $this->supplierModel = new Supplier();
        $this->productModel = new WholesaleProduct();
        $this->purchaseModel = new Purchase();
        $this->paymentModel = new PurchasePayment();
    }

    /**
     * Inventory Dashboard
     */
    public function index()
    {
        $this->requireAdmin();
        
        // Get statistics
        $supplierStats = $this->supplierModel->getSupplierStats();
        $productStats = $this->productModel->getProductStats();
        $purchaseStats = $this->purchaseModel->getPurchaseStats();
        $paymentStats = $this->paymentModel->getPaymentStats();
        
        // Get recent data
        $recentPurchases = $this->purchaseModel->getRecentPurchases(5);
        $recentPayments = $this->paymentModel->getRecentPayments(5);
        $lowStockProducts = $this->productModel->getLowStockProducts();
        
        $this->view('admin/inventory/index', [
            'supplierStats' => $supplierStats,
            'productStats' => $productStats,
            'purchaseStats' => $purchaseStats,
            'paymentStats' => $paymentStats,
            'recentPurchases' => $recentPurchases,
            'recentPayments' => $recentPayments,
            'lowStockProducts' => $lowStockProducts,
            'title' => 'Inventory Management'
        ]);
    }

    /**
     * Suppliers Management
     */
    public function suppliers()
    {
        $this->requireAdmin();
        
        $suppliers = $this->supplierModel->getAllSuppliers();
        $stats = $this->supplierModel->getSupplierStats();
        
        $this->view('admin/inventory/suppliers', [
            'suppliers' => $suppliers,
            'stats' => $stats,
            'title' => 'Suppliers Management'
        ]);
    }

    /**
     * Add Supplier
     */
    public function addSupplier()
    {
        $this->requireAdmin();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'supplier_name' => trim($_POST['supplier_name'] ?? ''),
                'phone' => trim($_POST['phone'] ?? ''),
                'email' => trim($_POST['email'] ?? ''),
                'address' => trim($_POST['address'] ?? ''),
                'contact_person' => trim($_POST['contact_person'] ?? ''),
                'status' => $_POST['status'] ?? 'active'
            ];
            
            // Validation
            $errors = [];
            if (empty($data['supplier_name'])) {
                $errors[] = 'Supplier name is required';
            }
            
            if ($this->supplierModel->existsByName($data['supplier_name'])) {
                $errors[] = 'Supplier name already exists';
            }
            
            if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'Invalid email format';
            }
            
            if (empty($errors)) {
                if ($this->supplierModel->createSupplier($data)) {
                    $this->setFlash('success', 'Supplier added successfully');
                } else {
                    $this->setFlash('error', 'Failed to add supplier');
                }
            } else {
                $this->setFlash('error', implode('<br>', $errors));
            }
            
            $this->redirect('admin/inventory/supplier');
        }
        
        $this->view('admin/inventory/add/supplier', [
            'title' => 'Add Supplier'
        ]);
    }

    /**
     * Edit Supplier
     */
    public function editSupplier($id = null)
    {
        $this->requireAdmin();
        
        if (!$id) {
            $this->redirect('admin/inventory/supplier');
        }
        
        $supplier = $this->supplierModel->getSupplierById($id);
        if (!$supplier) {
            $this->setFlash('error', 'Supplier not found');
            $this->redirect('admin/inventory/supplier');
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'supplier_name' => trim($_POST['supplier_name'] ?? ''),
                'phone' => trim($_POST['phone'] ?? ''),
                'email' => trim($_POST['email'] ?? ''),
                'address' => trim($_POST['address'] ?? ''),
                'contact_person' => trim($_POST['contact_person'] ?? ''),
                'status' => $_POST['status'] ?? 'active'
            ];
            
            // Validation
            $errors = [];
            if (empty($data['supplier_name'])) {
                $errors[] = 'Supplier name is required';
            }
            
            if ($this->supplierModel->existsByName($data['supplier_name'], $id)) {
                $errors[] = 'Supplier name already exists';
            }
            
            if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'Invalid email format';
            }
            
            if (empty($errors)) {
                if ($this->supplierModel->updateSupplier($id, $data)) {
                    $this->setFlash('success', 'Supplier updated successfully');
                } else {
                    $this->setFlash('error', 'Failed to update supplier');
                }
            } else {
                $this->setFlash('error', implode('<br>', $errors));
            }
            
            $this->redirect('admin/inventory/supplier');
        }
        
        $this->view('admin/inventory/edit/supplier', [
            'supplier' => $supplier,
            'title' => 'Edit Supplier'
        ]);
    }

    /**
     * Delete Supplier
     */
    public function deleteSupplier($id = null)
    {
        $this->requireAdmin();
        
        if (!$id) {
            $this->redirect('admin/inventory/supplier');
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if ($this->supplierModel->deleteSupplier($id)) {
                $this->setFlash('success', 'Supplier deleted successfully');
            } else {
                $this->setFlash('error', 'Failed to delete supplier');
            }
        }
        
        $this->redirect('admin/inventory/suppliers');
    }

    /**
     * Toggle Supplier Status
     */
    public function toggleSupplierStatus($id = null)
    {
        $this->requireAdmin();
        
        if (!$id) {
            $this->jsonResponse(['success' => false, 'message' => 'Invalid supplier ID'], 400);
            return;
        }
        
        if ($this->supplierModel->toggleStatus($id)) {
            $this->jsonResponse(['success' => true, 'message' => 'Supplier status updated']);
        } else {
            $this->jsonResponse(['success' => false, 'message' => 'Failed to update status'], 500);
        }
    }

    /**
     * Products Management
     */
    public function products()
    {
        $this->requireAdmin();
        
        $products = $this->productModel->getAllProductsWithSuppliers();
        $stats = $this->productModel->getProductStats();
        $suppliers = $this->supplierModel->getActiveSuppliers();
        
        $this->view('admin/inventory/products', [
            'products' => $products,
            'stats' => $stats,
            'suppliers' => $suppliers,
            'title' => 'Products Management'
        ]);
    }

    /**
     * Scan barcode/SKU (AJAX)
     */
    public function scanBarcode()
    {
        $this->requireAdmin();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !$this->isAjaxRequest()) {
            $this->jsonResponse(['success' => false, 'message' => 'Invalid request'], 400);
            return;
        }

        $barcode = trim($_POST['barcode'] ?? '');
        
        if (empty($barcode)) {
            $this->jsonResponse(['success' => false, 'message' => 'Barcode/SKU is required'], 400);
            return;
        }

        // Search by SKU or barcode
        $product = $this->productModel->findBySku($barcode);
        
        if ($product) {
            $this->jsonResponse([
                'success' => true,
                'product' => $product,
                'message' => 'Product found'
            ]);
        } else {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Product not found with this barcode/SKU'
            ], 404);
        }
    }

    /**
     * Add Product
     */
    public function addProduct()
    {
        $this->requireAdmin();
        
        $suppliers = $this->supplierModel->getActiveSuppliers();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'supplier_id' => (int)($_POST['supplier_id'] ?? 0),
                'product_name' => trim($_POST['product_name'] ?? ''),
                'type' => trim($_POST['type'] ?? ''),
                'cost_amount' => (float)($_POST['cost_amount'] ?? 0),
                'selling_price' => !empty($_POST['selling_price']) ? (float)$_POST['selling_price'] : null,
                'quantity' => (int)($_POST['quantity'] ?? 0),
                'min_stock_level' => (int)($_POST['min_stock_level'] ?? 10),
                'description' => trim($_POST['description'] ?? ''),
                'sku' => trim($_POST['sku'] ?? ''),
                'status' => $_POST['status'] ?? 'active'
            ];
            
            // Validation
            $errors = [];
            if (empty($data['supplier_id'])) {
                $errors[] = 'Supplier is required';
            }
            
            if (empty($data['product_name'])) {
                $errors[] = 'Product name is required';
            }
            
            if ($data['cost_amount'] <= 0) {
                $errors[] = 'Cost amount must be greater than 0';
            }
            
            if (!empty($data['sku']) && $this->productModel->existsBySku($data['sku'])) {
                $errors[] = 'SKU already exists';
            }
            
            if (empty($errors)) {
                if ($this->productModel->createProduct($data)) {
                    $this->setFlash('success', 'Product added successfully');
                } else {
                    $this->setFlash('error', 'Failed to add product');
                }
            } else {
                $this->setFlash('error', implode('<br>', $errors));
            }
            
            $this->redirect('admin/inventory/products');
        }
        
        $this->view('admin/inventory/add-product', [
            'suppliers' => $suppliers,
            'title' => 'Add Product'
        ]);
    }

    /**
     * Edit Product
     */
    public function editProduct($id = null)
    {
        $this->requireAdmin();
        
        if (!$id) {
            $this->redirect('admin/inventory/products');
        }
        
        $product = $this->productModel->getProductById($id);
        if (!$product) {
            $this->setFlash('error', 'Product not found');
            $this->redirect('admin/inventory/products');
        }
        
        $suppliers = $this->supplierModel->getActiveSuppliers();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'supplier_id' => (int)($_POST['supplier_id'] ?? 0),
                'product_name' => trim($_POST['product_name'] ?? ''),
                'type' => trim($_POST['type'] ?? ''),
                'cost_amount' => (float)($_POST['cost_amount'] ?? 0),
                'selling_price' => !empty($_POST['selling_price']) ? (float)$_POST['selling_price'] : null,
                'quantity' => (int)($_POST['quantity'] ?? 0),
                'min_stock_level' => (int)($_POST['min_stock_level'] ?? 10),
                'description' => trim($_POST['description'] ?? ''),
                'sku' => trim($_POST['sku'] ?? ''),
                'status' => $_POST['status'] ?? 'active'
            ];
            
            // Validation
            $errors = [];
            if (empty($data['supplier_id'])) {
                $errors[] = 'Supplier is required';
            }
            
            if (empty($data['product_name'])) {
                $errors[] = 'Product name is required';
            }
            
            if ($data['cost_amount'] <= 0) {
                $errors[] = 'Cost amount must be greater than 0';
            }
            
            if (!empty($data['sku']) && $this->productModel->existsBySku($data['sku'], $id)) {
                $errors[] = 'SKU already exists';
            }
            
            if (empty($errors)) {
                if ($this->productModel->updateProduct($id, $data)) {
                    $this->setFlash('success', 'Product updated successfully');
                } else {
                    $this->setFlash('error', 'Failed to update product');
                }
            } else {
                $this->setFlash('error', implode('<br>', $errors));
            }
            
            $this->redirect('admin/inventory/products');
        }
        
        $this->view('admin/inventory/edit-product', [
            'product' => $product,
            'suppliers' => $suppliers,
            'title' => 'Edit Product'
        ]);
    }

    /**
     * Delete Product
     */
    public function deleteProduct($id = null)
    {
        $this->requireAdmin();
        
        if (!$id) {
            $this->redirect('admin/inventory/products');
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if ($this->productModel->deleteProduct($id)) {
                $this->setFlash('success', 'Product deleted successfully');
            } else {
                $this->setFlash('error', 'Failed to delete product');
            }
        }
        
        $this->redirect('admin/inventory/products');
    }

    /**
     * Purchases Management
     */
    public function purchases()
    {
        $this->requireAdmin();
        
        $purchases = $this->purchaseModel->getAllPurchases();
        $stats = $this->purchaseModel->getPurchaseStats();
        $products = $this->productModel->getAllProductsWithSuppliers();
        $suppliers = $this->supplierModel->getActiveSuppliers();
        
        $this->view('admin/inventory/purchases', [
            'purchases' => $purchases,
            'stats' => $stats,
            'products' => $products,
            'suppliers' => $suppliers,
            'title' => 'Purchases Management'
        ]);
    }

    /**
     * Add Purchase
     */
    public function addPurchase()
    {
        $this->requireAdmin();
        
        $products = $this->productModel->getAllProductsWithSuppliers();
        $suppliers = $this->supplierModel->getActiveSuppliers();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'product_id' => (int)($_POST['product_id'] ?? 0),
                'supplier_id' => (int)($_POST['supplier_id'] ?? 0),
                'quantity' => (int)($_POST['quantity'] ?? 0),
                'unit_cost' => (float)($_POST['unit_cost'] ?? 0),
                'total_amount' => (float)($_POST['total_amount'] ?? 0),
                'payment_method' => $_POST['payment_method'] ?? 'cod',
                'status' => $_POST['status'] ?? 'pending',
                'purchase_date' => $_POST['purchase_date'] ?? date('Y-m-d H:i:s'),
                'expected_delivery' => !empty($_POST['expected_delivery']) ? $_POST['expected_delivery'] : null,
                'notes' => trim($_POST['notes'] ?? '')
            ];
            
            // Validation
            $errors = [];
            if (empty($data['product_id'])) {
                $errors[] = 'Product is required';
            }
            
            if (empty($data['supplier_id'])) {
                $errors[] = 'Supplier is required';
            }
            
            if ($data['quantity'] <= 0) {
                $errors[] = 'Quantity must be greater than 0';
            }
            
            if ($data['unit_cost'] <= 0) {
                $errors[] = 'Unit cost must be greater than 0';
            }
            
            if ($data['total_amount'] <= 0) {
                $errors[] = 'Total amount must be greater than 0';
            }
            
            if (empty($errors)) {
                if ($this->purchaseModel->createPurchase($data)) {
                    // Update product quantity
                    $product = $this->productModel->getProductById($data['product_id']);
                    if ($product) {
                        $newQuantity = $product['quantity'] + $data['quantity'];
                        $this->productModel->updateQuantity($data['product_id'], $newQuantity);
                    }
                    
                    $this->setFlash('success', 'Purchase added successfully');
                } else {
                    $this->setFlash('error', 'Failed to add purchase');
                }
            } else {
                $this->setFlash('error', implode('<br>', $errors));
            }
            
            $this->redirect('admin/inventory/purchases');
        }
        
        $this->view('admin/inventory/add-purchase', [
            'products' => $products,
            'suppliers' => $suppliers,
            'title' => 'Add Purchase'
        ]);
    }

    /**
     * Edit Purchase
     */
    public function editPurchase($id = null)
    {
        $this->requireAdmin();
        
        if (!$id) {
            $this->redirect('admin/inventory/purchases');
        }
        
        $purchase = $this->purchaseModel->getPurchaseById($id);
        if (!$purchase) {
            $this->setFlash('error', 'Purchase not found');
            $this->redirect('admin/inventory/purchases');
        }
        
        $products = $this->productModel->getAllProductsWithSuppliers();
        $suppliers = $this->supplierModel->getActiveSuppliers();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'product_id' => (int)($_POST['product_id'] ?? 0),
                'supplier_id' => (int)($_POST['supplier_id'] ?? 0),
                'quantity' => (int)($_POST['quantity'] ?? 0),
                'unit_cost' => (float)($_POST['unit_cost'] ?? 0),
                'total_amount' => (float)($_POST['total_amount'] ?? 0),
                'payment_method' => $_POST['payment_method'] ?? 'cod',
                'status' => $_POST['status'] ?? 'pending',
                'purchase_date' => $_POST['purchase_date'] ?? date('Y-m-d H:i:s'),
                'expected_delivery' => !empty($_POST['expected_delivery']) ? $_POST['expected_delivery'] : null,
                'notes' => trim($_POST['notes'] ?? '')
            ];
            
            // Validation
            $errors = [];
            if (empty($data['product_id'])) {
                $errors[] = 'Product is required';
            }
            
            if (empty($data['supplier_id'])) {
                $errors[] = 'Supplier is required';
            }
            
            if ($data['quantity'] <= 0) {
                $errors[] = 'Quantity must be greater than 0';
            }
            
            if ($data['unit_cost'] <= 0) {
                $errors[] = 'Unit cost must be greater than 0';
            }
            
            if ($data['total_amount'] <= 0) {
                $errors[] = 'Total amount must be greater than 0';
            }
            
            if (empty($errors)) {
                if ($this->purchaseModel->updatePurchase($id, $data)) {
                    $this->setFlash('success', 'Purchase updated successfully');
                } else {
                    $this->setFlash('error', 'Failed to update purchase');
                }
            } else {
                $this->setFlash('error', implode('<br>', $errors));
            }
            
            $this->redirect('admin/inventory/purchases');
        }
        
        $this->view('admin/inventory/edit-purchase', [
            'purchase' => $purchase,
            'products' => $products,
            'suppliers' => $suppliers,
            'title' => 'Edit Purchase'
        ]);
    }

    /**
     * Delete Purchase
     */
    public function deletePurchase($id = null)
    {
        $this->requireAdmin();
        
        if (!$id) {
            $this->redirect('admin/inventory/purchases');
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if ($this->purchaseModel->deletePurchase($id)) {
                $this->setFlash('success', 'Purchase deleted successfully');
            } else {
                $this->setFlash('error', 'Failed to delete purchase');
            }
        }
        
        $this->redirect('admin/inventory/purchases');
    }

    /**
     * Payments Management
     */
    public function payments()
    {
        $this->requireAdmin();
        
        $payments = $this->paymentModel->getAllPayments();
        $stats = $this->paymentModel->getPaymentStats();
        $purchases = $this->purchaseModel->getAllPurchases();
        
        $this->view('admin/inventory/payments', [
            'payments' => $payments,
            'stats' => $stats,
            'purchases' => $purchases,
            'title' => 'Payments Management'
        ]);
    }

    /**
     * Add Payment
     */
    public function addPayment()
    {
        $this->requireAdmin();
        
        $purchases = $this->purchaseModel->getAllPurchases();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'purchase_id' => (int)($_POST['purchase_id'] ?? 0),
                'payment_method' => $_POST['payment_method'] ?? 'cash',
                'amount' => (float)($_POST['amount'] ?? 0),
                'payment_date' => $_POST['payment_date'] ?? date('Y-m-d H:i:s'),
                'reference_number' => trim($_POST['reference_number'] ?? ''),
                'notes' => trim($_POST['notes'] ?? '')
            ];
            
            // Validation
            $errors = [];
            if (empty($data['purchase_id'])) {
                $errors[] = 'Purchase is required';
            }
            
            if ($data['amount'] <= 0) {
                $errors[] = 'Amount must be greater than 0';
            }
            
            if (empty($errors)) {
                if ($this->paymentModel->createPayment($data)) {
                    // Update purchase status
                    $this->paymentModel->updatePurchaseStatus($data['purchase_id']);
                    
                    $this->setFlash('success', 'Payment added successfully');
                } else {
                    $this->setFlash('error', 'Failed to add payment');
                }
            } else {
                $this->setFlash('error', implode('<br>', $errors));
            }
            
            $this->redirect('admin/inventory/payments');
        }
        
        $this->view('admin/inventory/add-payment', [
            'purchases' => $purchases,
            'title' => 'Add Payment'
        ]);
    }

    /**
     * Delete Payment
     */
    public function deletePayment($id = null)
    {
        $this->requireAdmin();
        
        if (!$id) {
            $this->redirect('admin/inventory/payments');
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Get payment details before deletion
            $payment = $this->paymentModel->getPaymentById($id);
            
            if ($this->paymentModel->deletePayment($id)) {
                // Update purchase status after payment deletion
                if ($payment) {
                    $this->paymentModel->updatePurchaseStatus($payment['purchase_id']);
                }
                
                $this->setFlash('success', 'Payment deleted successfully');
            } else {
                $this->setFlash('error', 'Failed to delete payment');
            }
        }
        
        $this->redirect('admin/inventory/payments');
    }
}