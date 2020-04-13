<?php

namespace Ci4adminrbac\Controllers\Admin;

use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

use Ci4adminrbac\Controllers\BaseController;

use Ci4adminrbac\Libraries\Adminlte;
use Ci4adminrbac\Libraries\Auth;

use Ci4adminrbac\Models\MHelper;
use Ci4adminrbac\Models\MModule;
use Ci4adminrbac\Models\MPrivilege;

use Ci4adminrbac\Config\Services;

class AdminController extends BaseController
{

	protected $selectedModule = -1;

	/**
	 * @var Adminlte
	 */
	protected $adminlte = null;

	/**
	 * @var Auth
	 */
	protected $auth = null;

	public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
	{
		$this->helpers[] = 'admin';
		$this->helpers[] = 'form';
		parent::initController($request, $response, $logger);

		$this->adminlte = new Adminlte();
		$this->auth = Services::auth();

		$session = Services::session();
		$this->vars['status'] = $session->getFlashdata('status');
		$this->vars['message'] = $session->getFlashdata('message');
	}

	protected function render()
	{
		$this->adminlte->selectModule($this->selectedModule);
		$this->adminlte->setVars($this->vars);
		$this->adminlte->renderContentView();
	}

	protected function setFlashMessage($status = 'info', $message)
	{
		$session = Services::session();

		$session->setFlashdata('status', json_encode($status));
		$session->setFlashdata('message', json_encode($message));
	}

	protected function responeDataTable($dataTable)
	{
		if ($this->request->isAJAX()) {
			if ($requestData = $this->request->getGet()) {
				$output = $dataTable->getOutput($requestData);
				return $this->respond($output, 200);
			}
		} else {
			return $this->failForbidden();
		}
	}

	protected function deleteData($table, $sourceTable = null, $delete = false)
	{
		$status = 'error';
		$message = 'Data gagal dihapus!';
		if ($this->request->getGet(null)) {

			$paramId = $this->request->getGet('id');
			$sourceTable = isset($sourceTable) ? $sourceTable : $table;

			$mHelper = new MHelper();
			$userGroup = $mHelper->rowArray($sourceTable, ['*'], ['id' => $paramId]);
			if ($userGroup) {
				$userId = $this->auth->getUserData('id');
				$fill = array(
					'is_deleted' => 1,
					'deleted_by' => $userId
				);
				try {
					$mHelper->updateWithId($paramId, $table, $fill);
					$status = 'success';
					$message = "Data berhasil dihapus!";
				} catch (\Throwable $th) {
					$message = $th->getMessage();
				}
			} else {
				$status = 'warning';
				$message = "Data tidak ditemukan!";
			}
		}
		return parent::outputJson($status, $message);
	}
}
