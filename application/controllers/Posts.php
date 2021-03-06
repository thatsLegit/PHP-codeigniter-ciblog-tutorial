<?php

class Posts extends CI_Controller{
    //Mapping : ciblog/posts/index : index
    //Mapping : ciblog/posts : index
    //Mapping : ciblog/posts/... : post-one, post-two
    //Mapping : ciblog/posts/view/... : post-one, post-two
    public function index($offset = 0){

        //pagination
        $this->load->library('pagination');

        $config['base_url'] = base_url().'posts/index/';
        //total rows : all the rows in the database
        $config['total_rows'] = $this->db->count_all('posts'); 
        $config['per_page'] = 2;
        $config['uri_segment'] = 3;
        // Produces: class="pagination-link"
        $config['attributes'] = array('class' => 'pagination-link');

        $this->pagination->initialize($config);

        //request the model
        $data['title'] = 'Latest Posts';
        $data['posts'] = $this->post_model->get_posts(FALSE, $config['per_page'], 
            $offset);
        
        //construct views with queried data
        $this->load->view('templates/header');
        $this->load->view('posts/index', $data);
        $this->load->view('templates/footer');
    }

    public function view($slug = NULL){
        $data['post'] = $this->post_model->get_posts($slug);
        $post_id = $data['post']['id'];
        $data['comments'] = $this->comment_model->get_comments($post_id);

        if(empty($data['post'])){
            show_404();
        }

        //getting the post title
        $data['title'] = $data['post']['title'];

        $this->load->view('templates/header');
        $this->load->view('posts/view', $data);
        $this->load->view('templates/footer');
    }

    public function create(){
        //check login
        if(!$this->session->userdata('logged_in')){
            redirect('users/login');
        }

        //this part of data is used in the if only
        $data['title'] = 'Create post';
        $data['categories'] = $this->post_model->get_categories();

        //validation rules
        $this->form_validation->set_rules('title', 'Title', 'required');
        $this->form_validation->set_rules('body', 'Body', 'required');

        //Just load the posts/create form till not submitted
        if($this->form_validation->run() === FALSE){
            $this->load->view('templates/header');
            $this->load->view('posts/create', $data);
            $this->load->view('templates/footer');
        } else
        //load a model id rules are followed 
        {
            //upload images config
            $config['upload_path'] = './assets/images/posts';
			$config['allowed_types'] = 'gif|jpg|png';
			$config['max_size'] = '2048';
			$config['max_width'] = '2000';
			$config['max_height'] = '2000';

            $this->load->library('upload', $config);

            if(!$this->upload->do_upload()){
                $errors = array('error' => $this->upload->display_errors());
                $post_image = 'noimage.jpg';
            } else {
                $data = array('upload_data' => $this->upload->data());
                $post_image = $_FILES['userfile']['name'];
            }
            
            //remark : images are uploaded to a folder
            //but the filename is in the db
            $this->post_model->create_post($post_image);

            //session message
            $this->session->set_flashdata('post_created', 
                'Your post has been created');

            redirect('posts');
        }
    }

    public function delete($id){
        //check login
        if(!$this->session->userdata('logged_in')){
            redirect('users/login');
        }

        $this->post_model->delete_post($id);

        //session message
        $this->session->set_flashdata('post_deleted', 
        'Your post has been deleted');

        redirect('posts');
    }

    public function edit($slug){
        //check login
        if(!$this->session->userdata('logged_in')){
            redirect('users/login');
        }

        $data['post'] = $this->post_model->get_posts($slug);
        $data['categories'] = $this->post_model->get_categories();

        //check user
        if($this->session->userdata('user_id') != $data['post']['user_id']){
            redirect('posts');
        }

        if(empty($data['post'])){
            show_404();
        }

        //getting the post title
        $data['title'] = 'Edit Post';

        $this->load->view('templates/header');
        $this->load->view('posts/edit', $data);
        $this->load->view('templates/footer');
    }

    public function update(){
        //check login
        if(!$this->session->userdata('logged_in')){
            redirect('users/login');
        }

        $this->post_model->update_post();

        //session message
        $this->session->set_flashdata('post_updated', 
        'Your post has been updated');

        redirect('posts');
    }
}

?>