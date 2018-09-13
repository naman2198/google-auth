<?php
namespace App\Controller;

use App\Controller\AppController;
use Cake\Auth\DefaultPasswordHasher;
/**
 * Users Controller
 *
 * @property \App\Model\Table\UsersTable $Users
 *
 * @method \App\Model\Entity\User[]|\Cake\Datasource\ResultSetInterface paginate($object = null, array $settings = [])
 */
class UsersController extends AppController
{

    /**
     * Index method
     *
     * @return \Cake\Http\Response|void
     */
    public function initialize()
        {
            parent::initialize();
            $this->Auth->allow(['logout','login','add']);
        }
    public function index()
    {
        $users = $this->paginate($this->Users);

        $this->set(compact('users'));
    }

    /**
     * View method
     *
     * @param string|null $id User id.
     * @return \Cake\Http\Response|void
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view($id = null)
    {
        $user = $this->Users->get($id, [
            'contain' => ['Articles']
        ]);

        $this->set('user', $user);
    }

    /**
     * Add method
     *
     * @return \Cake\Http\Response|null Redirects on successful add, renders view otherwise.
     */
    public function add()
    {
        $user = $this->Users->newEntity();
        if ($this->request->is('post')) {
            $user = $this->Users->patchEntity($user, $this->request->getData());
            if ($this->Users->save($user)) {
                $this->Flash->success(__('The user has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The user could not be saved. Please, try again.'));
        }
        $this->set(compact('user'));
    }

    /**
     * Edit method
     *
     * @param string|null $id User id.
     * @return \Cake\Http\Response|null Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Network\Exception\NotFoundException When record not found.
     */
    public function edit($id = null)
    {
        $user = $this->Users->get($id, [
            'contain' => []
        ]);
        if ($this->request->is(['patch', 'post', 'put'])) {
            $user = $this->Users->patchEntity($user, $this->request->getData());
            if ($this->Users->save($user)) {
                $this->Flash->success(__('The user has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The user could not be saved. Please, try again.'));
        }
        $this->set(compact('user'));
    }

    /**
     * Delete method
     *
     * @param string|null $id User id.
     * @return \Cake\Http\Response|null Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete($id = null)
    {
        $this->request->allowMethod(['post', 'delete']);
        $user = $this->Users->get($id);
        if ($this->Users->delete($user)) {
            $this->Flash->success(__('The user has been deleted.'));
        } else {
            $this->Flash->error(__('The user could not be deleted. Please, try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }
    public function login()
{
    
    if ($this->request->is('post')) {
        //pr($this->Auth); die;
        $user = $this->Auth->identify();
        //pr($user); die;
        if ($user) {
            $this->Auth->setUser($user);
            return $this->redirect(['action' => 'index']);
        }
        $this->Flash->error('Your username or password is incorrect.');
    }
}
  public function logout() 
  {
    $this->Flash->success('You are now logged out.');
    return $this->redirect($this->Auth->logout());
  }

 public function isAuthorized($user)
  {
    // By default deny access.
    return true;
  }
  public function googlelogin() {

    $client = new \Google_Client();
    $client->setClientId('98759135663-9o47urbm3jf6dqesuravjk3efu322s1a.apps.googleusercontent.com');
    $client->setClientSecret('Fk3J9IIk2AHdbfTnAfV9cQpz');
    $client->setRedirectUri('http://localhost/cms/users/index');

    $client->setScopes(array(
        "https://www.googleapis.com/auth/userinfo.profile",
        'https://www.googleapis.com/auth/userinfo.email'
    ));
    $url = $client->createAuthUrl();
    $this->redirect($url);
}

public function confirmLogin() {
    $client = new \Google_Client();
    $client->setClientId('98759135663-9o47urbm3jf6dqesuravjk3efu322s1a.apps.googleusercontent.com');
    $client->setClientSecret('Fk3J9IIk2AHdbfTnAfV9cQpz');
    $client->setRedirectUrl('http://localhost/cms/users');

    $client->setScopes(array(
        "https://www.googleapis.com/auth/userinfo.profile",
        'https://www.googleapis.com/auth/userinfo.email'
    ));
    $client->setApprovalPrompt('auto');

    if (isset($this->request->query['code'])) {
        $client->authenticate($this->request->query['code']);
        $this->request->Session()->write('access_token', $client->getAccessToken());
    }

    if ($this->request->Session()->check('access_token') && ($this->request->Session()->read('access_token'))) {
        $client->setAccessToken($this->request->Session()->read('access_token'));
    }

    if ($client->getAccessToken()) {
        $this->request->Session()->write('access_token', $client->getAccessToken());
        $oauth2 = new Google_Service_Oauth2($client);
        $user = $oauth2->userinfo->get();
        try {
            if (!empty($user)) {
                if (preg_match("/(@mydomain\.com)$/", $user['email'])) {
                        $result = $this->Users->find('all')
                            ->where(['email' => $user['email']])
                            ->first();
                    if ($result) {
                        $this->Auth->setUser($result->toArray());
                        $this->redirect($this->Auth->redirectUrl());
                    } else {

                        $data = array();
                        $data['email'] = $user['email'];
                        $data['first_name'] = $user['first_name'];
                        $data['last_name'] = $user['last_name'];
                        $data['socialId'] = $user['id'];
                        //$data matches my Users table

                        $entity = $this->Users->newEntity($data);
                        if ($this->Users->save($entity)) {
                            $data['id'] = $entity->id;
                            $this->Auth->setUser($data);
                            $this->redirect($this->Auth->redirectUrl());
                        } else {
                            $this->Flash->set('Logging error');
                            $this->redirect(['action' => 'login']);
                        }
                    }
                } else {
                    $this->Flash->set('Forbidden');
                    $this->redirect(['action' => 'login']);
                }
            } else {
                $this->Flash->set('Google infos not found');
                $this->redirect(['action' => 'login']);
            }
        } catch (\Exception $e) {
            $this->Flash->set('Google error');
            return $this->redirect(['action' => 'login']);
        }
    }
}

}
