# -*- mode: ruby -*-
# vi: set ft=ruby :

# All Vagrant configuration is done below. The "2" in Vagrant.configure
# configures the configuration version (compatibility related).
# Don't change it.
Vagrant.configure(2) do |config|
  # The most common configuration options are documented and commented below.
  # For a complete reference, please see the online documentation at
  # https://docs.vagrantup.com.

  # Every Vagrant development environment requires a box. The current
  # configuration uses Ubuntu Trusty Tahr 64
  config.vm.box = "ubuntu/xenial64"

  # Share an additional host folder to the guest VM. The first argument is
  # the path on the host to the actual folder (relative to Vagrantfile).
  # The second argument is the (absolute) path on the guest to mount the folder.
  # Note: The directory will be recursively created in the guest, if it
  # does not exist.
  # Syncing from the "/home/vagrant/pde_source/" folder to a "destination" folder
  # can be done with "sudo rsync -rvC --update /home/vagrant/pde_source/ /destination"
  # Note that in order for any external changes to take effect, the "vagrant rsync-auto"
  # command must be active.
  config.vm.synced_folder ".", "/home/ubuntu/pde_source/",
    type: "rsync", rsync__auto: true,
    rsync__exclude: [
        '.git', '.idea', 'Symfony/app/cache', 'Symfony/files',
        'Symfony/app/logs', 'Symfony/app/config/parameters.yml', 'Symfony/bin', 'Symfony/vendor',
        'Symfony/composer.phar', '*.DS_Store'
    ]

  # Create a forwarded port mapping which allows access to a specific port
  # within the machine from a port on the host machine. Current configuration
  # forwards guest's port 80 to host's port 8080
  config.vm.network "forwarded_port", guest: 80, host: 8080

  # Provider-specific configuration.
  config.vm.provider "virtualbox" do |vb|
    # Customize the amount of memory on the VM (at least 1,5 Gb in order to build PHP):
    vb.memory = "1536"
  end

  # Use the bash script "installation.sh" instead of a provisioning script
end

